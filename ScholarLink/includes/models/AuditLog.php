<?php

declare(strict_types=1);

class AuditLog
{
    public static function create(int $userId, string $action, string $entityType, ?int $entityId = null, ?array $details = null): int
    {
        $db = Database::getInstance();
        $db->getConnection()->prepare('
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ')->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return (int)$db->lastInsertId();
    }

    public static function getByAction(string $action, int $limit = 100, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT al.*, u.email, u.role, u.first_name, u.last_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.action = ?
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ', [$action, $limit, $offset]);
    }

    public static function getByEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT al.*, u.email, u.first_name, u.last_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.entity_type = ? AND al.entity_id = ?
            ORDER BY al.created_at DESC
            LIMIT ?
        ', [$entityType, $entityId, $limit]);
    }

    public static function getAll(int $limit = 100, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT al.*, u.email, u.role, u.first_name, u.last_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ', [$limit, $offset]);
    }

    public static function count(): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM audit_logs');
        return (int)($result['count'] ?? 0);
    }
}
