<?php

declare(strict_types=1);

class ScholarshipApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case '':
                if ($method === 'GET') $this->list();
                elseif ($method === 'POST') $this->create();
                break;
            case 'search':
                $this->search();
                break;
            case 'categories':
                $this->categories();
                break;
            default:
                if ($method === 'GET' && is_numeric($action)) {
                    $this->show((int)$action);
                } elseif ($method === 'PUT' && is_numeric($action)) {
                    $this->update((int)$action);
                } elseif ($method === 'DELETE' && is_numeric($action)) {
                    $this->delete((int)$action);
                } elseif (is_numeric($action) && $id === 'requirements') {
                    $this->requirements((int)$action);
                } elseif (is_numeric($action) && $id === 'requirements' && isset($_SERVER['REQUEST_METHOD'])) {
                    $this->manageRequirements((int)$action);
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function list(): void
    {
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        $category = $_GET['category'] ?? null;
        $status = 'published';
        $isVerified = $_GET['is_verified'] ?? null;

        $filters = [];
        if ($category) $filters['category'] = $category;
        if ($isVerified !== null) $filters['is_verified'] = (int)$isVerified;
        $filters['status'] = $status;

        $results = Scholarship::getAll($limit, $offset, $filters);
        $total = Scholarship::count($filters);

        jsonResponse([
            'data'  => $results,
            'meta'  => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    private function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $category = $_GET['category'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        if (empty($query) && empty($category)) {
            jsonResponse(['error' => 'Search query or category is required'], 422);
        }

        $filters = [
            'status' => 'published',
        ];
        if (!empty($query)) $filters['search'] = $query;
        if (!empty($category)) $filters['category'] = $category;

        $results = Scholarship::getAll($limit, $offset, $filters);
        $total = Scholarship::count($filters);

        jsonResponse([
            'data'  => $results,
            'meta'  => [
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
                'query'  => $query,
            ],
        ]);
    }

    private function show(int $id): void
    {
        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            jsonResponse(['error' => 'Scholarship not found'], 404);
        }
        if ($scholarship['status'] !== 'published') {
            $user = AuthMiddleware::handle();
            if (!$user || (int)$user['id'] !== (int)$scholarship['organization_id']) {
                jsonResponse(['error' => 'Scholarship not found'], 404);
            }
        }
        jsonResponse(['data' => $scholarship]);
    }

    private function create(): void
    {
        $user = RoleMiddleware::requireRole('organization');
        $input = $this->getInput();

        $required = ['title', 'description', 'amount', 'category_id', 'deadline'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 422);
            }
        }

        $data = [
            'organization_id'  => (int)$user['id'],
            'title'            => $input['title'],
            'description'      => $input['description'],
            'amount'           => (float)$input['amount'],
            'category_id'      => (int)$input['category_id'],
            'deadline'         => $input['deadline'],
            'status'           => $input['status'] ?? 'draft',
            'requirements_json' => $input['requirements_json'] ?? null,
        ];

        $scholarship = Scholarship::create($data);

        if (isset($input['requirements']) && is_array($input['requirements'])) {
            foreach ($input['requirements'] as $index => $req) {
                Scholarship::addRequirement((int)$scholarship['id'], [
                    'requirement_type' => $req['requirement_type'] ?? 'other',
                    'label'            => $req['label'] ?? 'Requirement',
                    'description'      => $req['description'] ?? null,
                    'is_required'      => $req['is_required'] ?? 1,
                    'sort_order'       => $index,
                ]);
            }
        }

        AuditLog::create((int)$user['id'], 'scholarship_created', 'scholarships', (int)$scholarship['id']);

        jsonResponse(['message' => 'Scholarship created', 'data' => $scholarship], 201);
    }

    private function update(int $id): void
    {
        $user = RoleMiddleware::requireRole('organization');
        $input = $this->getInput();

        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            jsonResponse(['error' => 'Scholarship not found'], 404);
        }

        if ((int)$scholarship['organization_id'] !== (int)$user['id']) {
            jsonResponse(['error' => 'You can only edit your own scholarships'], 403);
        }

        $updateData = [];
        $allowedFields = ['title', 'description', 'amount', 'category_id', 'deadline', 'status', 'is_verified'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updateData[$field] = $input[$field];
            }
        }
        if (array_key_exists('requirements_json', $input)) {
            $updateData['requirements_json'] = $input['requirements_json'];
        }

        Scholarship::update($id, $updateData);

        AuditLog::create((int)$user['id'], 'scholarship_updated', 'scholarships', $id);

        jsonResponse(['message' => 'Scholarship updated', 'data' => Scholarship::find($id)]);
    }

    private function delete(int $id): void
    {
        $user = RoleMiddleware::requireRole('organization');

        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            jsonResponse(['error' => 'Scholarship not found'], 404);
        }

        if ((int)$scholarship['organization_id'] !== (int)$user['id']) {
            jsonResponse(['error' => 'You can only delete your own scholarships'], 403);
        }

        Scholarship::delete($id);

        AuditLog::create((int)$user['id'], 'scholarship_deleted', 'scholarships', $id);

        jsonResponse(['message' => 'Scholarship deleted']);
    }

    private function categories(): void
    {
        $categories = Scholarship::getCategories();
        jsonResponse(['data' => $categories]);
    }

    private function requirements(int $scholarshipId): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = $this->getInput();

        if ($method === 'GET') {
            $reqs = Scholarship::getRequirements($scholarshipId);
            jsonResponse(['data' => $reqs]);
        } elseif ($method === 'POST') {
            $user = RoleMiddleware::requireRole('organization');
            $scholarship = Scholarship::find($scholarshipId);
            if (!$scholarship || (int)$scholarship['organization_id'] !== (int)$user['id']) {
                jsonResponse(['error' => 'Unauthorized'], 403);
            }
            $reqId = Scholarship::addRequirement($scholarshipId, [
                'requirement_type' => $input['requirement_type'] ?? 'other',
                'label'            => $input['label'] ?? 'Requirement',
                'description'      => $input['description'] ?? null,
                'is_required'      => $input['is_required'] ?? 1,
                'sort_order'       => $input['sort_order'] ?? 0,
            ]);
            jsonResponse(['message' => 'Requirement added', 'id' => $reqId], 201);
        } else {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }

    private function manageRequirements(int $scholarshipId): void
    {
        // Handled in requirements() above
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
