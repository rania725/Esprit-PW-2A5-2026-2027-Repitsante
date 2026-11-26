<?php
declare(strict_types=1);

final class PasswordResetController
{
    private PasswordResetModel $passwordResets;

    public function __construct()
    {
        $this->passwordResets = new PasswordResetModel();
    }

    public function requestCode(string $method): void
    {
        $email = '';
        $errors = [];
        if ($method === 'POST') {
            requireCsrf();
            $email = trim(requestText($_POST, 'email'));
            $request = $this->passwordResets->issueCode($email, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            if ($request !== null) {
                sendPasswordResetCode($request['email'], $request['code']);
            }

            $_SESSION['password_reset_email_hash'] = PasswordResetModel::emailHash($email);
            unset($_SESSION['password_reset_verified_hash'], $_SESSION['password_reset_verified_at']);
            redirect('password-code');
        }

        render('front/password-forgot', 'Mot de passe oublié', compact('email', 'errors'));
    }

    public function verifyCode(string $method): void
    {
        $emailHash = $_SESSION['password_reset_email_hash'] ?? null;
        if (!is_string($emailHash)) {
            redirect('password-forgot');
        }

        $errors = [];
        if ($method === 'POST') {
            requireCsrf();
            $code = requestText($_POST, 'code');
            if (strlen($code) > 32) {
                $code = '';
            }
            $verifiedCodeHash = $this->passwordResets->verifyCode($emailHash, $code);
            if ($verifiedCodeHash === null) {
                $errors['code'] = 'Ce code n’est pas valide ou a expiré. Demande un nouveau code.';
                http_response_code(422);
            } else {
                $_SESSION['password_reset_verified_hash'] = $verifiedCodeHash;
                $_SESSION['password_reset_verified_at'] = time();
                session_regenerate_id(true);
                redirect('password-new');
            }
        }

        render('front/password-code', 'Saisir le code', compact('errors'));
    }

    public function setNewPassword(string $method): void
    {
        $emailHash = $_SESSION['password_reset_email_hash'] ?? null;
        $verifiedCodeHash = $_SESSION['password_reset_verified_hash'] ?? null;
        $verifiedAt = $_SESSION['password_reset_verified_at'] ?? null;
        if (
            !is_string($emailHash)
            || !is_string($verifiedCodeHash)
            || !is_int($verifiedAt)
            || $verifiedAt > time()
            || time() - $verifiedAt > 600
        ) {
            unset($_SESSION['password_reset_verified_hash'], $_SESSION['password_reset_verified_at']);
            redirect(is_string($emailHash) ? 'password-code' : 'password-forgot');
        }

        $errors = [];
        if ($method === 'POST') {
            requireCsrf();
            $password = requestText($_POST, 'password');
            $confirmation = requestText($_POST, 'password_confirm');
            if (!$this->validPassword($password)) {
                $errors['password'] = 'Choisis un mot de passe d’au moins 8 caractères et de 72 octets maximum.';
            }
            if ($password !== $confirmation) {
                $errors['password_confirm'] = 'Les deux mots de passe doivent être identiques.';
            }

            if ($errors === []) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($this->passwordResets->updatePassword($emailHash, $verifiedCodeHash, $passwordHash)) {
                    $_SESSION = [];
                    session_regenerate_id(true);
                    flash('Ton mot de passe a été modifié. Tu peux te connecter.');
                    redirect('login');
                }

                unset($_SESSION['password_reset_verified_hash'], $_SESSION['password_reset_verified_at']);
                flash('Le code n’est plus valide. Demande un nouveau code.');
                redirect('password-code');
            }
        }

        render('front/password-new', 'Nouveau mot de passe', compact('errors'));
    }

    private function validPassword(string $password): bool
    {
        return strlen($password) <= 72
            && preg_match('/^.{8,}$/usD', $password) === 1
            && !str_contains($password, "\0");
    }
}
