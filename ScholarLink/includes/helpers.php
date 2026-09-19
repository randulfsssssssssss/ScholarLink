<?php

declare(strict_types=1);

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function redirect(string $path, int $code = 302): void
{
    http_response_code($code);
    header('Location: ' . $path);
    exit;
}

function sanitizeInput(?string $input): ?string
{
    if ($input === null) {
        return null;
    }
    return trim(html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword(string $password): bool
{
    return strlen($password) >= 8;
}

function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function formatDate(string $date): string
{
    return date('M j, Y', strtotime($date));
}

function formatDateTime(string $datetime): string
{
    return date('M j, Y g:i A', strtotime($datetime));
}

function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function uploadFile(array $file, string $uploadDir, array $allowedExtensions): ?array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    $maxSize = 5242880;
    if ($file['size'] > $maxSize) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    if (!isset($allowedMimes[$extension]) || $mime !== $allowedMimes[$extension]) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid(bin2hex(random_bytes(8)) . '_', true) . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    chmod($destination, 0644);

    return [
        'file_name'      => $file['name'],
        'file_path'      => $filename,
        'file_size'      => $file['size'],
        'file_type'      => $mime,
        'file_extension' => $extension,
    ];
}

function getUserFromRequest(): ?User
{
    $session = SessionManager::getInstance();
    if (!$session->isLoggedIn()) {
        return null;
    }
    $userId = $session->get('user_id');
    if (!$userId) {
        return null;
    }
    return User::find((int)$userId);
}

function requireLogin(): User
{
    $user = getUserFromRequest();
    if (!$user) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $user;
}
