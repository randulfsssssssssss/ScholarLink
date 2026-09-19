<?php

declare(strict_types=1);

class Message
{
    public static function create(int $senderId, int $recipientId, string $subject, string $body, ?int $applicationId = null, ?int $scholarshipId = null): int
    {
        $db = Database::getInstance();
        $db->getConnection()->prepare('
            INSERT INTO messages (sender_id, recipient_id, subject, body, application_id, scholarship_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ')->execute([$senderId, $recipientId, $subject, $body, $applicationId, $scholarshipId]);

        return (int)$db->lastInsertId();
    }

    public static function getByRecipient(int $recipientId, bool $unreadOnly = false, int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        $params = [$recipientId];
        $where = '';

        if ($unreadOnly) {
            $where = 'AND m.is_read = 0';
        }

        return $db->fetchAll("
            SELECT m.*,
                   s.email AS sender_email, s.first_name AS sender_first_name, s.last_name AS sender_last_name,
                   s.role AS sender_role
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            WHERE m.recipient_id = ?
            $where
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ", [...$params, $limit, $offset]);
    }

    public static function getBySender(int $senderId, int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT m.*,
                   r.email AS recipient_email, r.first_name AS recipient_first_name, r.last_name AS recipient_last_name,
                   r.role AS recipient_role
            FROM messages m
            JOIN users r ON m.recipient_id = r.id
            WHERE m.sender_id = ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ', [$senderId, $limit, $offset]);
    }

    public static function find(int $messageId): ?array
    {
        $db = Database::getInstance();
        return $db->fetch('
            SELECT m.*,
                   s.email AS sender_email, s.first_name AS sender_first_name, s.last_name AS sender_last_name,
                   s.role AS sender_role,
                   r.email AS recipient_email, r.first_name AS recipient_first_name, r.last_name AS recipient_last_name,
                   r.role AS recipient_role
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.recipient_id = r.id
            WHERE m.id = ?
        ', [$messageId]);
    }

    public static function canAccess(int $userId, int $messageId): bool
    {
        $db = Database::getInstance();
        $result = $db->fetch(
            'SELECT id FROM messages WHERE id = ? AND (sender_id = ? OR recipient_id = ?)',
            [$messageId, $userId, $userId]
        );
        return $result !== null;
    }

    public static function markAsRead(int $messageId, int $userId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare(
            'UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?'
        )->execute([$messageId, $userId]);
    }

    public static function markAsUnread(int $messageId, int $userId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare(
            'UPDATE messages SET is_read = 0 WHERE id = ? AND recipient_id = ?'
        )->execute([$messageId, $userId]);
    }

    public static function countUnread(int $recipientId): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0', [$recipientId]);
        return (int)($result['count'] ?? 0);
    }

    public static function delete(int $messageId, int $userId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare(
            'DELETE FROM messages WHERE id = ? AND (sender_id = ? OR recipient_id = ?)'
        )->execute([$messageId, $userId, $userId]);
    }
}
