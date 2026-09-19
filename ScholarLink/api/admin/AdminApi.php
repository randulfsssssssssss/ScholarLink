<?php

declare(strict_types=1);

class AdminApi
{
    public function route(string $method, string $action, string $id): void
    {
        RoleMiddleware::requireRole('admin');

        switch ($action) {
            case 'users':
                if ($method === 'GET') $this->listUsers();
                break;
            case 'scholarships':
                if ($method === 'GET') $this->listScholarships();
                break;
            case 'applications':
                if ($method === 'GET') $this->listApplications();
                break;
            case 'stats':
                if ($method === 'GET') $this->stats();
                break;
            case 'audit':
                if ($method === 'GET') $this->auditLog();
                break;
            case 'categories':
                if ($method === 'POST') $this->createCategory();
                elseif ($method === 'GET') $this->listCategories();
                break;
            default:
                if (is_numeric($action)) {
                    if ($id === 'users' && $method === 'PUT') {
                        $this->updateUser((int)$action);
                    } elseif ($id === 'scholarships' && $method === 'PUT') {
                        $this->updateScholarship((int)$action);
                    } elseif ($method === 'DELETE') {
                        if ($id === 'users') $this->deleteUser((int)$action);
                        elseif ($id === 'scholarships') $this->deleteScholarship((int)$action);
                        elseif ($id === 'applications') $this->deleteApplication((int)$action);
                    }
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function listUsers(): void
    {
        $role = $_GET['role'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        if ($role) {
            $users = User::getAll($limit, $offset, $role);
        } else {
            $users = User::getAll($limit, $offset);
        }

        $total = User::countByRole($role ?: 'student') + User::countByRole($role ?: 'organization');

        jsonResponse([
            'data' => $users,
            'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    private function updateUser(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        $input = $this->getInput();
        $updateData = [];

        $allowedFields = ['first_name', 'last_name', 'school', 'graduation_year', 'organization_name', 'phone', 'is_active', 'email_verified'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updateData[$field] = $input[$field];
            }
        }

        if (isset($input['password'])) {
            $updateData['password'] = $input['password'];
        }

        if (!empty($updateData)) {
            User::update($userId, $updateData);
        }

        $adminUser = requireLogin();
        AuditLog::create((int)$adminUser['id'], 'user_updated', 'users', $userId);
        jsonResponse(['message' => 'User updated', 'data' => User::find($userId)]);
    }

    private function deleteUser(int $userId): void
    {
        $adminUser = requireLogin();

        if ((int)$adminUser['id'] === $userId) {
            jsonResponse(['error' => 'Cannot delete your own account'], 400);
        }

        User::delete($userId);
        AuditLog::create((int)$adminUser['id'], 'user_deleted', 'users', $userId);
        jsonResponse(['message' => 'User deleted']);
    }

    private function listScholarships(): void
    {
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $results = Scholarship::getAll($limit, $offset, []);
        jsonResponse(['data' => $results, 'meta' => ['limit' => $limit, 'offset' => $offset]]);
    }

    private function updateScholarship(int $id): void
    {
        $input = $this->getInput();
        $updateData = [];

        $allowedFields = ['title', 'description', 'amount', 'category_id', 'deadline', 'status', 'is_verified'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            Scholarship::update($id, $updateData);
        }

        $adminUser = requireLogin();
        AuditLog::create((int)$adminUser['id'], 'scholarship_updated', 'scholarships', $id);
        jsonResponse(['message' => 'Scholarship updated', 'data' => Scholarship::find($id)]);
    }

    private function deleteScholarship(int $id): void
    {
        $adminUser = requireLogin();
        Scholarship::delete($id);
        AuditLog::create((int)$adminUser['id'], 'scholarship_deleted', 'scholarships', $id);
        jsonResponse(['message' => 'Scholarship deleted']);
    }

    private function listApplications(): void
    {
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        $status = $_GET['status'] ?? null;

        $db = Database::getInstance();
        $params = [$limit, $offset];
        $where = '';

        if ($status) {
            $where = 'WHERE a.status = ?';
            $params = array_merge([$status], $params);
        }

        $sql = "
            SELECT a.*,
                   student.first_name AS student_first_name, student.last_name AS student_last_name,
                   student.email AS student_email,
                   s.title AS scholarship_title, s.amount,
                   org.organization_name
            FROM applications a
            JOIN users student ON a.user_id = student.id
            JOIN scholarships s ON a.scholarship_id = s.id
            JOIN users org ON a.organization_id = org.id
            $where
            ORDER BY a.updated_at DESC
            LIMIT ? OFFSET ?
        ";

        // Reorder params for LIMIT
        if ($status) {
            $sql = str_replace('LIMIT ? OFFSET ?', 'LIMIT ? OFFSET ?', $sql);
            $params = [$status, $limit, $offset];
        }

        $results = $db->fetchAll($sql, $params);
        jsonResponse(['data' => $results, 'meta' => ['limit' => $limit, 'offset' => $offset]]);
    }

    private function deleteApplication(int $id): void
    {
        $adminUser = requireLogin();
        $db = Database::getInstance();
        $db->getConnection()->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
        AuditLog::create((int)$adminUser['id'], 'application_deleted', 'applications', $id);
        jsonResponse(['message' => 'Application deleted']);
    }

    private function stats(): void
    {
        $db = Database::getInstance();

        $stats = [
            'users' => [
                'total'        => User::countByRole('student') + User::countByRole('organization'),
                'students'     => User::countByRole('student'),
                'organizations' => User::countByRole('organization'),
                'admins'       => User::countByRole('admin'),
            ],
            'scholarships' => [
                'total'       => Scholarship::count(),
                'published'   => Scholarship::count(['status' => 'published']),
                'draft'       => Scholarship::count(['status' => 'draft']),
                'verified'    => Scholarship::getVerifiedCount(),
            ],
            'applications' => Application::countByStatus(),
        ];

        $result = $db->fetchAll('
            SELECT MONTH(created_at) as month, COUNT(*) as count
            FROM applications
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY MONTH(created_at)
            ORDER BY month DESC
        ');
        $stats['applications']['monthly'] = $result;

        jsonResponse(['data' => $stats]);
    }

    private function auditLog(): void
    {
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $action = $_GET['action'] ?? null;

        if ($action) {
            $logs = AuditLog::getByAction($action, $limit, $offset);
        } else {
            $logs = AuditLog::getAll($limit, $offset);
        }

        jsonResponse([
            'data' => $logs,
            'meta' => ['total' => count($logs), 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    private function createCategory(): void
    {
        $input = $this->getInput();
        $db = Database::getInstance();

        $slug = $input['slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($input['name'] ?? '')));

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

    private function listCategories(): void
    {
        $db = Database::getInstance();
        $categories = $db->fetchAll('SELECT * FROM categories ORDER BY name ASC');
        jsonResponse(['data' => $categories]);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
