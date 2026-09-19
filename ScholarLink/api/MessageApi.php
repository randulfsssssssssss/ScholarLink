<?php

declare(strict_types=1);

class MessageApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case '':
                if ($method === 'GET') $this->inbox();
                elseif ($method === 'POST') $this->send();
                break;
            case 'sent':
                if ($method === 'GET') $this->sent();
                break;
            case 'unread-count':
                if ($method === 'GET') $this->unreadCount();
                break;
            case 'read':
                if ($method === 'POST') $this->markRead();
                break;
            default:
                if ($method === 'GET' && is_numeric($action)) {
                    $this->show((int)$action);
                } elseif ($method === 'DELETE' && is_numeric($action)) {
                    $this->delete((int)$action);
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function inbox(): void
    {
        $user = requireLogin();
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';

        $messages = Message::getByRecipient((int)$user['id'], $unreadOnly, $limit, $offset);

        jsonResponse([
            'data' => $messages,
            'meta' => ['total' => count($messages), 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    private function sent(): void
    {
        $user = requireLogin();
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $messages = Message::getBySender((int)$user['id'], $limit, $offset);

        jsonResponse([
            'data' => $messages,
            'meta' => ['total' => count($messages), 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    private function show(int $id): void
    {
        $user = requireLogin();

        if (!Message::canAccess((int)$user['id'], $id)) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        $message = Message::find($id);
        if (!$message) {
            jsonResponse(['error' => 'Message not found'], 404);
        }

        if ((int)$message['recipient_id'] === (int)$user['id']) {
            Message::markAsRead($id, (int)$user['id']);
        }

        jsonResponse(['data' => $message]);
    }

    private function send(): void
    {
        $user = requireLogin();
        $input = $this->getInput();

        $recipientId = (int)($input['recipient_id'] ?? 0);
        $subject = trim($input['subject'] ?? '');
        $body = trim($input['body'] ?? '');

        if ($recipientId <= 0 || empty($subject) || empty($body)) {
            jsonResponse(['error' => 'Recipient, subject, and body are required'], 422);
        }

        $recipient = User::find($recipientId);
        if (!$recipient) {
            jsonResponse(['error' => 'Recipient not found'], 404);
        }

        $messageId = Message::create(
            (int)$user['id'],
            $recipientId,
            $subject,
            $body,
            $input['application_id'] ?? null,
            $input['scholarship_id'] ?? null
        );

        AuditLog::create((int)$user['id'], 'message_sent', 'messages', $messageId, [
            'recipient_id' => $recipientId,
        ]);

        jsonResponse(['message' => 'Message sent', 'id' => $messageId], 201);
    }

    private function markRead(): void
    {
        $user = requireLogin();
        $input = $this->getInput();

        $messageIds = $input['message_ids'] ?? [];
        if (empty($messageIds)) {
            $messageId = (int)($input['message_id'] ?? 0);
            if ($messageId > 0) {
                Message::markAsRead($messageId, (int)$user['id']);
            }
        } else {
            foreach ($messageIds as $msgId) {
                Message::markAsRead((int)$msgId, (int)$user['id']);
            }
        }

        jsonResponse(['message' => 'Messages marked as read']);
    }

    private function unreadCount(): void
    {
        $user = requireLogin();
        $count = Message::countUnread((int)$user['id']);
        jsonResponse(['data' => ['unread_count' => $count]]);
    }

    private function delete(int $id): void
    {
        $user = requireLogin();

        if (!Message::canAccess((int)$user['id'], $id)) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        Message::delete($id, (int)$user['id']);
        jsonResponse(['message' => 'Message deleted']);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
