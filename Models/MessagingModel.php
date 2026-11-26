<?php
declare(strict_types=1);

final class MessagingModel
{
    public function listConversations(int $userId, string $query): array
    {
        $sql = 'SELECT conversations.id, peer.id AS peer_id, peer.username AS peer_name, peer.role AS peer_role,
                    latest.body AS last_message, latest.created_at AS last_message_at,
                    (SELECT COUNT(*) FROM messages unread
                     WHERE unread.conversation_id = conversations.id
                       AND unread.sender_id <> mine.user_id
                       AND unread.id > mine.last_read_message_id) AS unread_count
                FROM conversations
                INNER JOIN conversation_participants mine
                    ON mine.conversation_id = conversations.id AND mine.user_id = ?
                INNER JOIN conversation_participants other
                    ON other.conversation_id = conversations.id AND other.user_id <> mine.user_id
                INNER JOIN users peer ON peer.id = other.user_id
                LEFT JOIN messages latest
                    ON latest.id = (SELECT MAX(recent.id) FROM messages recent WHERE recent.conversation_id = conversations.id)';
        $parameters = [$userId];

        if ($query !== '') {
            $sql .= ' WHERE LOCATE(?, peer.username) > 0 OR LOCATE(?, COALESCE(latest.body, \'\')) > 0';
            $parameters[] = $query;
            $parameters[] = $query;
        }

        $sql .= ' ORDER BY latest.id DESC, conversations.id DESC';
        $statement = Database::getConnection()->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function conversation(int $conversationId, int $userId): ?array
    {
        $database = Database::getConnection();
        $statement = $database->prepare(
            'SELECT conversations.id, peer.id AS peer_id, peer.username AS peer_name, peer.role AS peer_role
             FROM conversations
             INNER JOIN conversation_participants mine
                 ON mine.conversation_id = conversations.id AND mine.user_id = ?
             INNER JOIN conversation_participants other
                 ON other.conversation_id = conversations.id AND other.user_id <> mine.user_id
             INNER JOIN users peer ON peer.id = other.user_id
             WHERE conversations.id = ?
             LIMIT 1'
        );
        $statement->execute([$userId, $conversationId]);
        $conversation = $statement->fetch();
        if (!$conversation) {
            return null;
        }

        $statement = $database->prepare(
            'SELECT messages.id, messages.sender_id, users.username AS sender_name,
                    messages.body, messages.created_at
             FROM messages
             INNER JOIN users ON users.id = messages.sender_id
             WHERE messages.conversation_id = ?
             ORDER BY messages.id ASC'
        );
        $statement->execute([$conversationId]);
        $conversation['messages'] = $statement->fetchAll();

        if ($conversation['messages'] !== []) {
            $lastMessage = end($conversation['messages']);
            $statement = $database->prepare(
                'UPDATE conversation_participants
                 SET last_read_message_id = ?
                 WHERE conversation_id = ? AND user_id = ?'
            );
            $statement->execute([(int) $lastMessage['id'], $conversationId, $userId]);
        }

        return $conversation;
    }

    public function startConversation(int $senderId, int $recipientId, string $body): ?int
    {
        $database = Database::getConnection();
        $database->beginTransaction();

        try {
            $statement = $database->prepare('SELECT id FROM users WHERE id = ? AND id <> ? FOR UPDATE');
            $statement->execute([$recipientId, $senderId]);
            if ($statement->fetchColumn() === false) {
                $database->rollBack();
                return null;
            }

            $statement = $database->prepare(
                'SELECT mine.conversation_id
                 FROM conversation_participants mine
                 INNER JOIN conversation_participants peer
                    ON peer.conversation_id = mine.conversation_id AND peer.user_id = ?
                 WHERE mine.user_id = ?
                 GROUP BY mine.conversation_id
                 HAVING (SELECT COUNT(*) FROM conversation_participants all_participants
                         WHERE all_participants.conversation_id = mine.conversation_id) = 2
                 ORDER BY mine.conversation_id DESC
                 LIMIT 1'
            );
            $statement->execute([$recipientId, $senderId]);
            $conversationId = $statement->fetchColumn();

            if ($conversationId === false) {
                $database->prepare('INSERT INTO conversations () VALUES ()')->execute();
                $conversationId = (int) $database->lastInsertId();
                $participant = $database->prepare(
                    'INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)'
                );
                $participant->execute([$conversationId, $senderId, $conversationId, $recipientId]);
            } else {
                $conversationId = (int) $conversationId;
            }

            $statement = $database->prepare(
                'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)'
            );
            $statement->execute([$conversationId, $senderId, $body]);
            $messageId = (int) $database->lastInsertId();

            $statement = $database->prepare(
                'UPDATE conversation_participants SET last_read_message_id = ?
                 WHERE conversation_id = ? AND user_id = ?'
            );
            $statement->execute([$messageId, $conversationId, $senderId]);
            $database->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')
                ->execute([$conversationId]);
            $database->commit();

            return $conversationId;
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public function sendReply(int $conversationId, int $senderId, string $body): bool
    {
        $database = Database::getConnection();
        $database->beginTransaction();

        try {
            $statement = $database->prepare(
                'SELECT conversation_id FROM conversation_participants
                 WHERE conversation_id = ? AND user_id = ? FOR UPDATE'
            );
            $statement->execute([$conversationId, $senderId]);
            if ($statement->fetchColumn() === false) {
                $database->rollBack();
                return false;
            }

            $statement = $database->prepare(
                'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)'
            );
            $statement->execute([$conversationId, $senderId, $body]);
            $messageId = (int) $database->lastInsertId();

            $statement = $database->prepare(
                'UPDATE conversation_participants SET last_read_message_id = ?
                 WHERE conversation_id = ? AND user_id = ?'
            );
            $statement->execute([$messageId, $conversationId, $senderId]);
            $database->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')
                ->execute([$conversationId]);
            $database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public function recipients(int $userId): array
    {
        $statement = Database::getConnection()->prepare(
            'SELECT id, username FROM users WHERE id <> ? ORDER BY username'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }
}
