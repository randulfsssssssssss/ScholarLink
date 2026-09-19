<?php

declare(strict_types=1);

class BookmarkApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case '':
                if ($method === 'GET') $this->list();
                elseif ($method === 'POST') $this->create();
                break;
            default:
                if ($method === 'DELETE' && is_numeric($action)) {
                    $this->delete((int)$action);
                } elseif ($method === 'GET' && $action === 'check' && is_numeric($id)) {
                    $this->check((int)$id);
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function list(): void
    {
        $user = requireLogin();
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        if ($user['role'] !== 'student') {
            jsonResponse(['error' => 'Only students can bookmark scholarships'], 403);
        }

        $bookmarks = Bookmark::getByUser((int)$user['id'], $limit, $offset);
        $total = Bookmark::countByUser((int)$user['id']);

        jsonResponse([
            'data' => $bookmarks,
            'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    private function create(): void
    {
        $user = requireLogin();
        $input = $this->getInput();

        if ($user['role'] !== 'student') {
            jsonResponse(['error' => 'Only students can bookmark scholarships'], 403);
        }

        $scholarshipId = (int)($input['scholarship_id'] ?? 0);
        if ($scholarshipId <= 0) {
            jsonResponse(['error' => 'Scholarship ID is required'], 422);
        }

        if (Bookmark::isBookmarked((int)$user['id'], $scholarshipId)) {
            jsonResponse(['message' => 'Already bookmarked'], 409);
        }

        Bookmark::create((int)$user['id'], $scholarshipId);
        AuditLog::create((int)$user['id'], 'bookmark_created', 'bookmarks', (int)$user['id'], [
            'scholarship_id' => $scholarshipId,
        ]);

        jsonResponse(['message' => 'Bookmark created'], 201);
    }

    private function delete(int $scholarshipId): void
    {
        $user = requireLogin();

        if ($user['role'] !== 'student') {
            jsonResponse(['error' => 'Only students can manage bookmarks'], 403);
        }

        Bookmark::delete((int)$user['id'], $scholarshipId);
        jsonResponse(['message' => 'Bookmark removed']);
    }

    private function check(int $scholarshipId): void
    {
        $user = requireLogin();
        $isBookmarked = Bookmark::isBookmarked((int)$user['id'], $scholarshipId);
        jsonResponse(['is_bookmarked' => $isBookmarked]);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
