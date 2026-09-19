<?php

declare(strict_types=1);

class Bookmark
{
    public static function create(int $userId, int $scholarshipId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare('
            INSERT IGNORE INTO bookmarks (user_id, scholarship_id) VALUES (?, ?)
        ')->execute([$userId, $scholarshipId]);
    }

    public static function delete(int $userId, int $scholarshipId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare(
            'DELETE FROM bookmarks WHERE user_id = ? AND scholarship_id = ?'
        )->execute([$userId, $scholarshipId]);
    }

    public static function getByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT b.id, b.created_at,
                   s.id AS scholarship_id, s.title, s.amount, s.deadline, s.description, s.is_verified,
                   c.name AS category_name, c.slug AS category_slug,
                   u.organization_name
            FROM bookmarks b
            JOIN scholarships s ON b.scholarship_id = s.id
            JOIN categories c ON s.category_id = c.id
            JOIN users u ON s.organization_id = u.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ', [$userId, $limit, $offset]);
    }

    public static function countByUser(int $userId): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM bookmarks WHERE user_id = ?', [$userId]);
        return (int)($result['count'] ?? 0);
    }

    public static function isBookmarked(int $userId, int $scholarshipId): bool
    {
        $db = Database::getInstance();
        $result = $db->fetch(
            'SELECT id FROM bookmarks WHERE user_id = ? AND scholarship_id = ? LIMIT 1',
            [$userId, $scholarshipId]
        );
        return $result !== null;
    }
}
