<?php

declare(strict_types=1);

class Document
{
    private static array $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    private static array $allowedMimeTypes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    public static function upload(array $file, int $userId, int $applicationId, int $requirementId): ?array
    {
        $config = (require SCHOLARLINK_ROOT . '/config/config.php')['upload'];
        $uploadDir = $config['dir'];
        $maxSize = $config['max_size'];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedExtensions, true)) {
            return null;
        }

        if ($file['size'] > $maxSize) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::$allowedMimeTypes[$extension]) || self::$allowedMimeTypes[$extension] !== $mime) {
            return null;
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $storedName = uniqid(bin2hex(random_bytes(8)) . '_', true) . '.' . $extension;
        $destination = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        chmod($destination, 0644);

        $db = Database::getInstance();
        $db->getConnection()->prepare('
            INSERT INTO application_documents
                (application_id, requirement_id, file_name, file_path, file_size, file_type, file_extension, uploader_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ')->execute([
            $applicationId,
            $requirementId,
            $file['name'],
            $storedName,
            $file['size'],
            $mime,
            $extension,
            $userId,
        ]);

        $docId = (int)$db->lastInsertId();

        AuditLog::create($userId, 'document_upload', 'documents', $docId, [
            'application_id' => $applicationId,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
        ]);

        $db->getConnection()->prepare('
            UPDATE application_requirements
            SET status = ?, document_id = ?
            WHERE application_id = ? AND requirement_id = ?
        ')->execute(['uploaded', $docId, $applicationId, $requirementId]);

        return self::find($docId);
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        return $db->fetch('
            SELECT d.*, a.user_id AS application_user_id, a.scholarship_id,
                   u.first_name AS uploader_name
            FROM application_documents d
            JOIN applications a ON d.application_id = a.id
            JOIN users u ON d.uploader_id = u.id
            WHERE d.id = ?
        ', [$id]);
    }

    public static function canAccess(int $userId, int $documentId, string $role): bool
    {
        $db = Database::getInstance();

        $doc = $db->fetch('
            SELECT d.id, a.user_id, a.organization_id, s.organization_id AS scholarship_org
            FROM application_documents d
            JOIN applications a ON d.application_id = a.id
            JOIN scholarships s ON a.scholarship_id = s.id
            WHERE d.id = ?
        ', [$documentId]);

        if (!$doc) {
            return false;
        }

        // Student: can only access their own documents
        if ($role === 'student') {
            return (int)$doc['user_id'] === $userId;
        }

        // Organization: can access documents of applications to their scholarships
        if ($role === 'organization') {
            return (int)$doc['scholarship_org'] === $userId;
        }

        // Admin: full access via audit path
        if ($role === 'admin') {
            AuditLog::create($userId, 'document_access_admin', 'documents', $documentId, []);
            return true;
        }

        return false;
    }

    public static function getDownloadPath(int $documentId): ?string
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT file_path FROM application_documents WHERE id = ? AND is_current = 1', [$documentId]);
        return $result ? $result['file_path'] : null;
    }

    public static function replace(int $documentId, array $file, int $userId): bool
    {
        $config = (require SCHOLARLINK_ROOT . '/config/config.php')['upload'];
        $uploadDir = $config['dir'];

        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$allowedExtensions, true)) {
            return false;
        }

        if ($file['size'] > $config['max_size']) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::$allowedMimeTypes[$extension]) || self::$allowedMimeTypes[$extension] !== $mime) {
            return false;
        }

        $db = Database::getInstance();

        // Mark old document as not current
        $db->getConnection()->prepare('UPDATE application_documents SET is_current = 0 WHERE id = ?')->execute([$documentId]);

        $storedName = uniqid(bin2hex(random_bytes(8)) . '_', true) . '.' . $extension;
        $destination = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return false;
        }

        chmod($destination, 0644);

        $doc = self::find($documentId);
        if (!$doc) {
            return false;
        }

        $db->getConnection()->prepare('
            INSERT INTO application_documents
                (application_id, requirement_id, file_name, file_path, file_size, file_type, file_extension, uploader_id, is_current, review_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ')->execute([
            $doc['application_id'],
            $doc['requirement_id'],
            $file['name'],
            $storedName,
            $file['size'],
            $mime,
            $extension,
            $userId,
            'uploaded',
        ]);

        $newDocId = (int)$db->lastInsertId();

        $db->getConnection()->prepare('
            UPDATE application_requirements
            SET status = ?, document_id = ?
            WHERE application_id = ? AND requirement_id = ?
        ')->execute(['uploaded', $newDocId, $doc['application_id'], $doc['requirement_id']]);

        AuditLog::create($userId, 'document_replace', 'documents', $documentId, [
            'new_document_id' => $newDocId,
        ]);

        return true;
    }

    public static function review(int $documentId, int $reviewerId, string $status, ?string $notes): bool
    {
        $validStatuses = ['missing', 'uploaded', 'reviewed', 'approved', 'rejected'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $db = Database::getInstance();
        $result = $db->getConnection()->prepare('
            UPDATE application_documents
            SET review_status = ?, review_notes = ?, reviewed_at = NOW(), reviewed_by = ?
            WHERE id = ? AND is_current = 1
        ')->execute([$status, $notes, $reviewerId, $documentId]);

        AuditLog::create($reviewerId, 'document_review', 'documents', $documentId, [
            'status' => $status,
            'notes' => $notes,
        ]);

        return $result;
    }

    public static function getAllowedExtensions(): array
    {
        return self::$allowedExtensions;
    }
}
