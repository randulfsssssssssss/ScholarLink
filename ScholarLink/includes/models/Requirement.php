<?php

declare(strict_types=1);

class Requirement
{
    public static function getTypes(): array
    {
        return [
            'transcript'       => 'Official Transcript',
            'recommendation'   => 'Letter of Recommendation',
            'essay'            => 'Personal Essay / Statement',
            'portfolio'        => 'Portfolio / Work Samples',
            'test_scores'      => 'Test Scores',
            'financial_info'   => 'Financial Information',
            'identification'   => 'Identification Document',
            'other'            => 'Other Document',
        ];
    }

    public static function create(int $scholarshipId, string $type, string $label, ?string $description = null, int $isRequired = 1, int $sortOrder = 0): int
    {
        $db = Database::getInstance();
        $db->getConnection()->prepare('
            INSERT INTO scholarship_requirements
                (scholarship_id, requirement_type, label, description, is_required, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ')->execute([$scholarshipId, $type, $label, $description, $isRequired, $sortOrder]);

        return (int)$db->lastInsertId();
    }

    public static function getForApplication(int $applicationId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll('
            SELECT ar.id AS app_req_id, ar.status, ar.updated_at,
                   r.requirement_type, r.label, r.description, r.is_required, r.sort_order,
                   d.id AS document_id, d.file_name, d.file_path, d.file_size, d.uploaded_at, d.review_status
            FROM application_requirements ar
            JOIN scholarship_requirements r ON ar.requirement_id = r.id
            LEFT JOIN application_documents d ON ar.document_id = d.id AND d.is_current = 1
            WHERE ar.application_id = ?
            ORDER BY r.sort_order ASC, r.id ASC
        ', [$applicationId]);
    }

    public static function updateStatus(int $applicationId, int $requirementId, string $status): bool
    {
        $validStatuses = ['missing', 'uploaded', 'reviewed', 'approved', 'rejected'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $db = Database::getInstance();
        return $db->getConnection()->prepare('
            UPDATE application_requirements
            SET status = ?
            WHERE application_id = ? AND requirement_id = ?
        ')->execute([$status, $applicationId, $requirementId]);
    }

    public static function getStatusLabels(): array
    {
        return [
            'missing'   => 'Missing',
            'uploaded'  => 'Uploaded',
            'reviewed'  => 'Reviewed',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
        ];
    }
}
