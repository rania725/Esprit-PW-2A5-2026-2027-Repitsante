<?php
declare(strict_types=1);

final class MessagingController
{
    private MessagingModel $messages;

    public function __construct(private AuthController $auth)
    {
        $this->messages = new MessagingModel();
    }

    public function inbox(): void
    {
        $account = $this->auth->requireAuth();
        $query = searchQuery();
        $conversations = $this->messages->listConversations($account->getId(), $query);
        foreach ($conversations as &$conversation) {
            $conversation['last_message_label'] = $this->dateLabel($conversation['last_message_at']);
        }
        unset($conversation);
        $section = 'messages';
        render('front/messages', 'Mes messages', compact('account', 'conversations', 'query', 'section'));
    }

    public function newMessage(string $method): void
    {
        $account = $this->auth->requireAuth();
        $values = ['recipient_id' => '', 'body' => ''];
        $errors = [];

        if ($method === 'POST') {
            requireCsrf();
            $values = [
                'recipient_id' => requestText($_POST, 'recipient_id'),
                'body' => trim(requestText($_POST, 'body')),
            ];
            $recipientId = filter_var($values['recipient_id'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($recipientId === false || $recipientId === $account->getId()) {
                $errors['recipient_id'] = 'Choisis un destinataire valide.';
            }
            if (!$this->validMessageBody($values['body'])) {
                $errors['body'] = 'Écris un message de 1 à 5 000 caractères.';
            }

            if ($errors === []) {
                $conversationId = $this->messages->startConversation(
                    $account->getId(),
                    (int) $recipientId,
                    $values['body']
                );
                if ($conversationId === null) {
                    $errors['recipient_id'] = 'Ce destinataire n’est plus disponible.';
                } else {
                    flash('Message envoyé.');
                    redirect('conversation', ['id' => $conversationId]);
                }
            }
            http_response_code(422);
        }

        $recipients = $this->messages->recipients($account->getId());
        $section = 'messages';
        render('front/message-new', 'Nouveau message', compact('account', 'values', 'errors', 'recipients', 'section'));
    }

    public function conversation(): void
    {
        $account = $this->auth->requireAuth();
        $conversationId = $this->conversationId($_GET);
        $conversation = $conversationId === null
            ? null
            : $this->messages->conversation($conversationId, $account->getId());

        if ($conversation === null) {
            errorPage(404, 'Cette conversation est introuvable.');
        }

        foreach ($conversation['messages'] as &$message) {
            $message['date_label'] = $this->dateLabel($message['created_at']);
        }
        unset($message);
        $section = 'messages';
        $errors = [];
        $body = '';
        render('front/conversation', 'Conversation', compact('account', 'conversation', 'section', 'errors', 'body'));
    }

    public function sendMessage(): void
    {
        $account = $this->auth->requireAuth();
        requireCsrf();

        $conversationId = $this->conversationId($_POST);
        if ($conversationId === null) {
            errorPage(404, 'Cette conversation est introuvable.');
        }

        $conversation = $this->messages->conversation($conversationId, $account->getId());
        if ($conversation === null) {
            errorPage(404, 'Cette conversation est introuvable.');
        }

        foreach ($conversation['messages'] as &$message) {
            $message['date_label'] = $this->dateLabel($message['created_at']);
        }
        unset($message);
        $body = trim(requestText($_POST, 'body'));
        $errors = [];
        if (!$this->validMessageBody($body)) {
            $errors['body'] = 'Écris un message de 1 à 5 000 caractères.';
        } elseif (!$this->messages->sendReply($conversationId, $account->getId(), $body)) {
            errorPage(404, 'Cette conversation est introuvable.');
        } else {
            flash('Message envoyé.');
            redirect('conversation', ['id' => $conversationId]);
        }

        http_response_code(422);
        $section = 'messages';
        render('front/conversation', 'Conversation', compact('account', 'conversation', 'section', 'errors', 'body'));
    }

    private function conversationId(array $input): ?int
    {
        $id = filter_var(requestText($input, 'id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        return $id === false ? null : $id;
    }

    private function validMessageBody(string $body): bool
    {
        return strlen($body) <= 20000
            && preg_match('/\A.{1,5000}\z/usD', $body) === 1
            && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $body) !== 1;
    }

    private function dateLabel(string $value): string
    {
        $date = new DateTimeImmutable($value);
        $today = new DateTimeImmutable('today');
        if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Aujourd’hui · ' . $date->format('H:i');
        }
        if ($date->format('Y-m-d') === $today->modify('-1 day')->format('Y-m-d')) {
            return 'Hier · ' . $date->format('H:i');
        }
        return $date->format('d/m/Y · H:i');
    }
}
