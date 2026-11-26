<?php
declare(strict_types=1);

final class PasswordResetModel
{
    private const CODE_LIFETIME_MINUTES = 10;
    private const RATE_WINDOW_SECONDS = 900;
    private const EMAIL_REQUEST_LIMIT = 3;
    private const IP_REQUEST_LIMIT = 10;
    private const MAX_CODE_ATTEMPTS = 5;

    public static function emailHash(string $email): string
    {
        $normalized = strlen($email) <= 150 ? strtolower(trim($email)) : '';
        return hash('sha256', $normalized);
    }

    public function issueCode(string $email, string $ip): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $validEmail = strlen($normalizedEmail) <= 150
            && filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) !== false;
        $emailHash = self::emailHash($email);
        $ipHash = hash('sha256', $ip);
        $database = Database::getConnection();

        $database->beginTransaction();
        try {
            $database->exec(
                'DELETE FROM password_reset_requests
                WHERE updated_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 7 DAY)'
            );
            $database->exec(
                'DELETE FROM password_reset_ip_limits
                WHERE updated_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 7 DAY)'
            );
            $statement = $database->prepare(
                'INSERT INTO password_reset_ip_limits (ip_hash, window_started_at, request_count)
                VALUES (?, CURRENT_TIMESTAMP, 0)
                ON DUPLICATE KEY UPDATE ip_hash = VALUES(ip_hash)'
            );
            $statement->execute([$ipHash]);
            $statement = $database->prepare(
                'SELECT request_count,
                    TIMESTAMPDIFF(SECOND, window_started_at, CURRENT_TIMESTAMP) AS window_age
                FROM password_reset_ip_limits WHERE ip_hash = ? FOR UPDATE'
            );
            $statement->execute([$ipHash]);
            $ipLimit = $statement->fetch();
            $ipWindowExpired = $ipLimit['window_age'] === null
                || (int) $ipLimit['window_age'] >= self::RATE_WINDOW_SECONDS;
            if ($ipWindowExpired) {
                $statement = $database->prepare(
                    'UPDATE password_reset_ip_limits
                    SET window_started_at = CURRENT_TIMESTAMP, request_count = 1
                    WHERE ip_hash = ?'
                );
                $statement->execute([$ipHash]);
                $ipRequestCount = 1;
            } else {
                $statement = $database->prepare(
                    'UPDATE password_reset_ip_limits
                    SET request_count = LEAST(request_count + 1, 65535)
                    WHERE ip_hash = ?'
                );
                $statement->execute([$ipHash]);
                $ipRequestCount = (int) $ipLimit['request_count'] + 1;
            }
            if ($ipRequestCount > self::IP_REQUEST_LIMIT || !$validEmail) {
                $database->commit();
                return null;
            }

            $statement = $database->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1 FOR UPDATE');
            $statement->execute([$normalizedEmail]);
            $user = $statement->fetch();

            $statement = $database->prepare(
                'INSERT INTO password_reset_requests
                    (email_hash, user_id, request_count, request_window_started_at, updated_at)
                VALUES (?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE email_hash = VALUES(email_hash)'
            );
            $statement->execute([$emailHash, $user ? (int) $user['id'] : null]);
            $statement = $database->prepare(
                'SELECT id, request_count,
                    TIMESTAMPDIFF(SECOND, request_window_started_at, CURRENT_TIMESTAMP) AS window_age,
                    TIMESTAMPDIFF(SECOND, last_requested_at, CURRENT_TIMESTAMP) AS cooldown_age
                FROM password_reset_requests WHERE email_hash = ? FOR UPDATE'
            );
            $statement->execute([$emailHash]);
            $request = $statement->fetch();
            $emailWindowExpired = $request['window_age'] === null
                || (int) $request['window_age'] >= self::RATE_WINDOW_SECONDS;
            $requestCount = $emailWindowExpired ? 1 : (int) $request['request_count'] + 1;

            $statement = $database->prepare(
                'UPDATE password_reset_requests
                SET user_id = ?, request_count = LEAST(?, 65535),
                    request_window_started_at = IF(?, CURRENT_TIMESTAMP, request_window_started_at)
                WHERE id = ?'
            );
            $statement->execute([
                $user ? (int) $user['id'] : null,
                $requestCount,
                $emailWindowExpired ? 1 : 0,
                (int) $request['id'],
            ]);

            $cooldownAge = $request['cooldown_age'];
            if (
                !$user
                || $requestCount > self::EMAIL_REQUEST_LIMIT
                || ($cooldownAge !== null && (int) $cooldownAge < 60)
            ) {
                $database->commit();
                return null;
            }

            $code = (string) random_int(100000, 999999);
            $codeHash = password_hash($code, PASSWORD_DEFAULT);
            $statement = $database->prepare(
                'UPDATE password_reset_requests
                SET code_hash = ?, attempts = 0, expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL '
                . self::CODE_LIFETIME_MINUTES . ' MINUTE), used_at = NULL, last_requested_at = CURRENT_TIMESTAMP
                WHERE id = ?'
            );
            $statement->execute([$codeHash, (int) $request['id']]);
            $database->commit();

            return ['email' => (string) $user['email'], 'code' => $code];
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public function verifyCode(string $emailHash, string $code): ?string
    {
        $database = Database::getConnection();
        $database->beginTransaction();
        try {
            $statement = $database->prepare(
                'SELECT id, code_hash, attempts, used_at,
                    TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP, expires_at) AS seconds_remaining
                FROM password_reset_requests WHERE email_hash = ? FOR UPDATE'
            );
            $statement->execute([$emailHash]);
            $request = $statement->fetch();
            if (
                !$request
                || $request['used_at'] !== null
                || $request['code_hash'] === null
                || $request['seconds_remaining'] === null
                || (int) $request['seconds_remaining'] <= 0
                || (int) $request['attempts'] >= self::MAX_CODE_ATTEMPTS
            ) {
                $database->commit();
                return null;
            }

            $statement = $database->prepare(
                'UPDATE password_reset_requests SET attempts = attempts + 1 WHERE id = ?'
            );
            $statement->execute([(int) $request['id']]);
            $database->commit();

            $wellFormed = preg_match('/\A[0-9]{6}\z/D', $code) === 1;
            return $wellFormed && password_verify($code, $request['code_hash'])
                ? (string) $request['code_hash']
                : null;
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public function updatePassword(string $emailHash, string $verifiedCodeHash, string $passwordHash): bool
    {
        $database = Database::getConnection();
        $database->beginTransaction();
        try {
            $statement = $database->prepare(
                'SELECT id, user_id, code_hash, used_at, attempts,
                    TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP, expires_at) AS seconds_remaining
                FROM password_reset_requests WHERE email_hash = ? FOR UPDATE'
            );
            $statement->execute([$emailHash]);
            $request = $statement->fetch();
            if (
                !$request
                || $request['used_at'] !== null
                || $request['code_hash'] === null
                || $request['seconds_remaining'] === null
                || (int) $request['seconds_remaining'] <= 0
                || (int) $request['attempts'] > self::MAX_CODE_ATTEMPTS
                || !hash_equals((string) $request['code_hash'], $verifiedCodeHash)
            ) {
                $database->rollBack();
                return false;
            }

            $statement = $database->prepare('UPDATE users SET password = ? WHERE id = ?');
            $statement->execute([$passwordHash, (int) $request['user_id']]);
            if ($statement->rowCount() !== 1) {
                $database->rollBack();
                return false;
            }

            $statement = $database->prepare(
                'UPDATE password_reset_requests
                SET code_hash = NULL, used_at = CURRENT_TIMESTAMP, expires_at = CURRENT_TIMESTAMP,
                    attempts = ? WHERE id = ?'
            );
            $statement->execute([self::MAX_CODE_ATTEMPTS, (int) $request['id']]);
            $database->commit();
            return true;
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }
}
