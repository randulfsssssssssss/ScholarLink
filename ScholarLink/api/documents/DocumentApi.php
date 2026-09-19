<?php

declare(strict_types=1);

class DocumentApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case 'upload':
                if ($method === 'POST') $this->upload();
                break;
            case 'replace':
                if ($method === 'POST' && is_numeric($id)) $this->replace((int)$id);
                break;
            case 'review':
                if ($method === 'PATCH' && is_numeric($id)) $this->review((int)$id);
                break;
            case 'download':
                if ($method === 'GET' && is_numeric($id)) $this->download((int)$id);
                break;
            default:
                if ($method === 'GET' && is_numeric($action)) {
                    $this->show((int)$action);
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function upload(): void
    {
        $user = requireLogin();

        if ($user['role'] !== 'student') {
            jsonResponse(['error' => 'Only students can upload documents'], 403);
        }

        $applicationId = (int)($_POST['application_id'] ?? 0);
        $requirementId = (int)($_POST['requirement_id'] ?? 0);

        if ($applicationId <= 0 || $requirementId <= 0) {
            jsonResponse(['error' => 'Application ID and requirement ID are required'], 422);
        }

        if (!Application::canAccess((int)$user['id'], $applicationId)) {
            jsonResponse(['error' => 'Access denied to this application'], 403);
        }

        if (!isset($_FILES['file'])) {
            jsonResponse(['error' => 'No file uploaded'], 422);
        }

        $document = Document::upload($_FILES['file'], (int)$user['id'], $applicationId, $requirementId);

        if (!$document) {
            jsonResponse(['error' => 'Upload failed. Ensure file is PDF, JPG, or PNG and under 5MB'], 400);
        }

        jsonResponse(['message' => 'Document uploaded', 'data' => $document], 201);
    }

    private function show(int $id): void
    {
        $user = requireLogin();

        if (!Document::canAccess((int)$user['id'], $id, $user['role'])) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        $doc = Document::find($id);
        if (!$doc) {
            jsonResponse(['error' => 'Document not found'], 404);
        }

        unset($doc['file_path']);
        jsonResponse(['data' => $doc]);
    }

    private function replace(int $id): void
    {
        $user = requireLogin();

        if ($user['role'] !== 'student') {
            jsonResponse(['error' => 'Only students can replace documents'], 403);
        }

        if (!Document::canAccess((int)$user['id'], $id, $user['role'])) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        if (!isset($_FILES['file'])) {
            jsonResponse(['error' => 'No file uploaded'], 422);
        }

        $config = (require SCHOLARLINK_ROOT . '/config/config.php')['upload'];
        $allowedExtensions = Document::getAllowedExtensions();

        $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            jsonResponse(['error' => 'File type not allowed'], 400);
        }

        if ($_FILES['file']['size'] > $config['max_size']) {
            jsonResponse(['error' => 'File exceeds maximum size of 5MB'], 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['file']['tmp_name']);
        $allowedMimes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (!in_array($mime, $allowedMimes, true)) {
            jsonResponse(['error' => 'Invalid file type'], 400);
        }

        if (Document::replace($id, $_FILES['file'], (int)$user['id'])) {
            jsonResponse(['message' => 'Document replaced']);
        }

        jsonResponse(['error' => 'Replace failed'], 500);
    }

    private function review(int $id): void
    {
        $user = requireLogin();

        if ($user['role'] !== 'organization' && $user['role'] !== 'admin') {
            jsonResponse(['error' => 'Only organizations and admins can review documents'], 403);
        }

        if (!Document::canAccess((int)$user['id'], $id, $user['role'])) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        $input = $this->getInput();
        $status = $input['review_status'] ?? 'uploaded';
        $notes = $input['review_notes'] ?? null;

        if (Document::review($id, (int)$user['id'], $status, $notes)) {
            jsonResponse(['message' => 'Document reviewed']);
        }

        jsonResponse(['error' => 'Review failed'], 500);
    }

    private function download(int $id): void
    {
        $user = requireLogin();

        if (!Document::canAccess((int)$user['id'], $id, $user['role'])) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        $doc = Document::find($id);
        if (!$doc) {
            jsonResponse(['error' => 'Document not found'], 404);
        }

        $config = (require SCHOLARLINK_ROOT . '/config/config.php')['upload'];
        $filePath = $config['dir'] . '/' . $doc['file_path'];

        if (!file_exists($filePath)) {
            jsonResponse(['error' => 'File not found on disk'], 404);
        }

        AuditLog::create((int)$user['id'], 'document_downloaded', 'documents', $id);

        $mime = $doc['file_type'] ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($doc['file_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
