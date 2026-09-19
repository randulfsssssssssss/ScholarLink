<?php

declare(strict_types=1);

class CategoryApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case '':
                if ($method === 'GET') $this->list();
                elseif ($method === 'POST') $this->create();
                break;
            default:
                if ($method === 'GET' && is_numeric($action)) {
                    $this->show((int)$action);
                } elseif ($method === 'PUT' && is_numeric($action)) {
                    $this->update((int)$action);
                } elseif ($method === 'DELETE' && is_numeric($action)) {
                    $this->delete((int)$action);
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function list(): void
    {
        $db = Database::getInstance();
        $categories = $db->fetchAll('SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC');
        jsonResponse(['data' => $categories]);
    }

    private function show(int $id): void
    {
        $db = Database::getInstance();
        $category = $db->fetch('SELECT * FROM categories WHERE id = ?', [$id]);
        if (!$category) {
            jsonResponse(['error' => 'Category not found'], 404);
        }
        jsonResponse(['data' => $category]);
    }

    private function create(): void
    {
        $user = AuthMiddleware::handle();
        if (!$user || $user['role'] !== 'admin') {
            jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = $this->getInput();
        $slug = $input['slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($input['name'] ?? '')));

        $db = Database::getInstance();
        $db->getConnection()->prepare('
            INSERT INTO categories (name, slug, description, is_active)
            VALUES (?, ?, ?, ?)
        ')->execute([
            $input['name'],
            $slug,
            $input['description'] ?? null,
            $input['is_active'] ?? 1,
        ]);

        jsonResponse(['message' => 'Category created', 'id' => (int)$db->lastInsertId()], 201);
    }

    private function update(int $id): void
    {
        $user = AuthMiddleware::handle();
        if (!$user || $user['role'] !== 'admin') {
            jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = $this->getInput();
        $slug = $input['slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($input['name'] ?? '')));

        $db = Database::getInstance();
        $db->getConnection()->prepare('
            UPDATE categories
            SET name = ?, slug = ?, description = ?, is_active = ?
            WHERE id = ?
        ')->execute([
            $input['name'],
            $slug,
            $input['description'] ?? null,
            $input['is_active'] ?? 1,
            $id,
        ]);

        jsonResponse(['message' => 'Category updated']);
    }

    private function delete(int $id): void
    {
        $user = AuthMiddleware::handle();
        if (!$user || $user['role'] !== 'admin') {
            jsonResponse(['error' => 'Admin access required'], 403);
        }

        $db = Database::getInstance();
        $db->getConnection()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        jsonResponse(['message' => 'Category deleted']);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
