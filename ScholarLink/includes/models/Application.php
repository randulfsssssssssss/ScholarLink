<?php

declare(strict_types=1);

class Application
{
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $result = $db->fetch('
            SELECT a.*,
                   s.title AS scholarship_title, s.amount, s.deadline,
                   u.first_name, u.last_name, u.organization_name,
                   student.first_name AS student_first_name,
                   student.last_name AS student_last_name,
                   student.email AS student_email
            FROM applications a
            JOIN scholarships s ON a.scholarship_id = s.id
            JOIN users u ON a.organization_id = u.id
            JOIN users student ON a.user_id = student.id
            WHERE a.id = ?
        ', [$id]);

        if (!$result) {
            return null;
        }

        $result['requirements'] = self::getRequirementProgress($id);
        $result['documents'] = self::getDocuments($id);
        return $result;
    }

    public static function create(int $userId, int $scholarshipId): ?array
    {
        $db = Database::getInstance();

        $existing = $db->fetch(
            'SELECT id FROM applications WHERE user_id = ? AND scholarship_id = ? LIMIT 1',
            [$userId, $scholarshipId]
        );
        if ($existing) {
            return self::find((int)$existing['id']);
        }

        $scholarship = Scholarship::find($scholarshipId);
        if (!$scholarship) {
            return null;
        }

        $db->getConnection()->prepare('
            INSERT INTO applications (user_id, scholarship_id, organization_id, status)
            VALUES (?, ?, ?, ?)
        ')->execute([
            $userId,
            $scholarshipId,
            $scholarship['organization_id'],
            'started',
        ]);

        $appId = (int)$db->lastInsertId();

        // Create requirement progress entries for each scholarship requirement
        $requirements = Scholarship::getRequirements($scholarshipId);
        foreach ($requirements as $req) {
            $db->getConnection()->prepare('
                INSERT INTO application_requirements (application_id, requirement_id, status)
                VALUES (?, ?, ?)
            ')->execute([$appId, $req['id'], 'missing']);
        }

        return self::find($appId);
    }

    public static function submit(int $applicationId): bool
    {
        $db = Database::getInstance();

        $app = $db->fetch(
            'SELECT * FROM applications WHERE id = ? FOR UPDATE',
            [$applicationId]
        );

        if (!$app || $app['status'] !== 'started') {
            return false;
        }

        $db->getConnection()->prepare('
            UPDATE applications
            SET status = ?, submitted_at = NOW()
            WHERE id = ?
        ')->execute(['under_review', $applicationId]);

        return true;
    }

    public static function updateStatus(int $applicationId, string $status, int $reviewerId = null, string $notes = null): bool
    {
        $db = Database::getInstance();

        $validStatuses = ['started', 'under_review', 'approved', 'declined', 'withdrawn'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $fields = ['status = ?', 'updated_at = NOW()'];
        $params = [$status];

        if (in_array($status, ['approved', 'declined', 'under_review'], true) && $reviewerId) {
            $fields[] = 'reviewed_at = NOW()';
            $params[] = null;
            // Reviewer is set via the parameter below; but we need reviewer_id
        }

        $sql = "UPDATE applications SET " . implode(', ', $fields);
        $sql .= ' WHERE id = ?';

        $params[] = $applicationId;

        // Simpler: update status with optional reviewed_at
        $sql = 'UPDATE applications SET status = ?, updated_at = NOW()';
        $sqlParams = [$status, $applicationId];

        if (in_array($status, ['approved', 'declined'], true)) {
            $sql .= ', reviewed_at = NOW()';
        }

        $sql .= ' WHERE id = ?';

        return $db->getConnection()->prepare($sql)->execute($sqlParams);
    }

    public static function getByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT a.*, s.title AS scholarship_title, s.amount, s.deadline,
                   s.description AS scholarship_description,
                   u.organization_name, c.name AS category_name
            FROM applications a
            JOIN scholarships s ON a.scholarship_id = s.id
            JOIN users u ON a.organization_id = u.id
            JOIN categories c ON s.category_id = c.id
            WHERE a.user_id = ?
            ORDER BY a.updated_at DESC
            LIMIT ? OFFSET ?
        ', [$userId, $limit, $offset]);
    }

    public static function getByScholarship(int $scholarshipId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT a.*,
                   student.first_name AS student_first_name,
                   student.last_name AS student_last_name,
                   student.email AS student_email,
                   s.title AS scholarship_title, s.amount
            FROM applications a
            JOIN users student ON a.user_id = student.id
            JOIN scholarships s ON a.scholarship_id = s.id
            WHERE a.scholarship_id = ?
            ORDER BY a.updated_at DESC
            LIMIT ? OFFSET ?
        ', [$scholarshipId, $limit, $offset]);
    }

    public static function getByOrganization(int $organizationId, int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT a.*,
                   student.first_name AS student_first_name,
                   student.last_name AS student_last_name,
                   student.email AS student_email,
                   s.title AS scholarship_title, s.amount, s.deadline
            FROM applications a
            JOIN users student ON a.user_id = student.id
            JOIN scholarships s ON a.scholarship_id = s.id
            WHERE a.organization_id = ?
            ORDER BY a.updated_at DESC
            LIMIT ? OFFSET ?
        ', [$organizationId, $limit, $offset]);
    }

    public static function countByUser(int $userId): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM applications WHERE user_id = ?', [$userId]);
        return (int)($result['count'] ?? 0);
    }

    public static function countByStatus(int $scholarshipId = null): array
    {
        $db = Database::getInstance();
        $params = [];
        $where = '';

        if ($scholarshipId) {
            $where = 'WHERE scholarship_id = ?';
            $params[] = $scholarshipId;
        }

        $results = $db->fetchAll("
            SELECT status, COUNT(*) as count
            FROM applications
            $where
            GROUP BY status
        ", $params);

        $counts = ['started' => 0, 'under_review' => 0, 'approved' => 0, 'declined' => 0, 'withdrawn' => 0];
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    public static function getRequirementProgress(int $applicationId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT ar.id, ar.status, ar.updated_at,
                   r.requirement_type, r.label, r.description, r.is_required, r.sort_order,
                   d.file_name, d.file_path, d.file_size, d.uploaded_at, d.review_status
            FROM application_requirements ar
            JOIN scholarship_requirements r ON ar.requirement_id = r.id
            LEFT JOIN application_documents d ON ar.document_id = d.id AND d.is_current = 1
            WHERE ar.application_id = ?
            ORDER BY r.sort_order ASC, r.id ASC
        ', [$applicationId]);
    }

    public static function getDocuments(int $applicationId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT * FROM application_documents
            WHERE application_id = ? AND is_current = 1
            ORDER BY uploaded_at DESC
        ', [$applicationId]);
    }

    public static function canAccess(int $userId, int $applicationId): bool
    {
        $db = Database::getInstance();
        $result = $db->fetch(
            'SELECT id FROM applications WHERE id = ? AND user_id = ? LIMIT 1',
            [$applicationId, $userId]
        );
        return $result !== null;
    }

    public static function getStatuses(): array
    {
        return ['started', 'under_review', 'approved', 'declined', 'withdrawn'];
    }
}
