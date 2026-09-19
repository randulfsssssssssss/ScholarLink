<?php

declare(strict_types=1);

class Scholarship
{
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $result = $db->fetch('
            SELECT s.*, c.name AS category_name, c.slug AS category_slug,
                   u.organization_name, u.first_name, u.last_name
            FROM scholarships s
            JOIN categories c ON s.category_id = c.id
            JOIN users u ON s.organization_id = u.id
            WHERE s.id = ?
        ', [$id]);

        if (!$result) {
            return null;
        }

        $result['requirements'] = self::getRequirements($id);
        return $result;
    }

    public static function getAll(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        $db = Database::getInstance();
        $params = [];
        $where = [];

        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where[] = '(c.slug = ? OR c.name = ?)';
            $params[] = $filters['category'];
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(s.title LIKE ? OR s.description LIKE ? OR u.organization_name LIKE ? OR c.name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['organization_id'])) {
            $where[] = 's.organization_id = ?';
            $params[] = $filters['organization_id'];
        }

        if (!empty($filters['is_verified'])) {
            $where[] = 's.is_verified = ?';
            $params[] = $filters['is_verified'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT s.id, s.title, s.amount, s.category_id, s.deadline, s.description, s.status, s.is_verified, s.published_at,
                   c.name AS category_name, c.slug AS category_slug,
                   u.organization_name, u.first_name, u.last_name, u.id AS organization_id
            FROM scholarships s
            JOIN categories c ON s.category_id = c.id
            JOIN users u ON s.organization_id = u.id
            $whereClause
            ORDER BY s.published_at DESC, s.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;

        return $db->fetchAll($sql, $params);
    }

    public static function count(array $filters = []): int
    {
        $db = Database::getInstance();
        $params = [];
        $where = [];

        if (!empty($filters['status'])) {
            $where[] = 's.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where[] = '(c.slug = ? OR c.name = ?)';
            $params[] = $filters['category'];
            $params[] = $filters['category'];
        }

        if (!empty($filters['organization_id'])) {
            $where[] = 's.organization_id = ?';
            $params[] = $filters['organization_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(s.title LIKE ? OR s.description LIKE ? OR u.organization_name LIKE ? OR c.name LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT COUNT(*) as count
            FROM scholarships s
            JOIN categories c ON s.category_id = c.id
            JOIN users u ON s.organization_id = u.id
            $whereClause
        ";

        $result = $db->fetch($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    public static function create(array $data): array
    {
        $db = Database::getInstance();

        $db->getConnection()->prepare('
            INSERT INTO scholarships
                (organization_id, title, description, amount, category_id, deadline, status, requirements_json, published_at)
            VALUES
                (:organization_id, :title, :description, :amount, :category_id, :deadline, :status, :requirements_json, :published_at)
        ')->execute([
            'organization_id'   => $data['organization_id'],
            'title'             => $data['title'],
            'description'       => $data['description'],
            'amount'            => $data['amount'],
            'category_id'       => $data['category_id'],
            'deadline'          => $data['deadline'],
            'status'            => $data['status'] ?? 'draft',
            'requirements_json' => isset($data['requirements_json']) ? json_encode($data['requirements_json']) : null,
            'published_at'      => ($data['status'] ?? 'draft') === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        $id = (int)$db->lastInsertId();
        return self::find($id) ?? [];
    }

    public static function update(int $scholarshipId, array $data): bool
    {
        $db = Database::getInstance();
        $fields = [];
        $params = ['scholarship_id' => $scholarshipId];

        $allowedFields = ['title', 'description', 'amount', 'category_id', 'deadline', 'status', 'is_verified'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (array_key_exists('requirements_json', $data)) {
            $fields[] = 'requirements_json = :requirements_json';
            $params['requirements_json'] = json_encode($data['requirements_json']);
        }

        if (array_key_exists('status', $data) && $data['status'] === 'published') {
            $fields[] = 'published_at = NOW()';
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE scholarships SET " . implode(', ', $fields) . " WHERE id = :scholarship_id";
        return $db->getConnection()->prepare($sql)->execute($params);
    }

    public static function delete(int $scholarshipId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare('DELETE FROM scholarships WHERE id = ?')->execute([$scholarshipId]);
    }

    public static function addRequirement(int $scholarshipId, array $data): int
    {
        $db = Database::getInstance();
        $db->getConnection()->prepare('
            INSERT INTO scholarship_requirements
                (scholarship_id, requirement_type, label, description, is_required, sort_order)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ')->execute([
            $scholarshipId,
            $data['requirement_type'],
            $data['label'],
            $data['description'] ?? null,
            $data['is_required'] ?? 1,
            $data['sort_order'] ?? 0,
        ]);

        return (int)$db->lastInsertId();
    }

    public static function getRequirements(int $scholarshipId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            'SELECT id, requirement_type, label, description, is_required, sort_order FROM scholarship_requirements WHERE scholarship_id = ? ORDER BY sort_order ASC, id ASC',
            [$scholarshipId]
        );
    }

    public static function deleteRequirement(int $requirementId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare('DELETE FROM scholarship_requirements WHERE id = ?')->execute([$requirementId]);
    }

    public static function getCategories(): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('SELECT id, name, slug, description FROM categories WHERE is_active = 1 ORDER BY name ASC');
    }

    public static function getPublishedCount(): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM scholarships WHERE status = ?', ['published']);
        return (int)($result['count'] ?? 0);
    }

    public static function getVerifiedCount(): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM scholarships WHERE is_verified = 1');
        return (int)($result['count'] ?? 0);
    }

    public static function getAllStatuses(): array
    {
        return ['draft', 'published', 'archived'];
    }
}
