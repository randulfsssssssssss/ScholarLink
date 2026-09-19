<?php

declare(strict_types=1);

class ApplicationApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case '':
                if ($method === 'GET') $this->list();
                elseif ($method === 'POST') $this->create();
                break;
            case 'status':
                if ($method === 'PATCH' && is_numeric($id)) {
                    $this->updateStatus((int)$id);
                }
                break;
            default:
                if (is_numeric($action)) {
                    if ($method === 'GET') {
                        $this->show((int)$action);
                    } elseif ($method === 'DELETE') {
                        $this->withdraw((int)$action);
                    }
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function list(): void
    {
        $user = requireLogin();
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        if ($user['role'] === 'student') {
            $applications = Application::getByUser((int)$user['id'], $limit, $offset);
        } elseif ($user['role'] === 'organization') {
            $applications = Application::getByOrganization((int)$user['id'], $limit, $offset);
        } else {
            jsonResponse(['error' => 'Invalid role for this action'], 403);
        }

        $total = count($applications);
        jsonResponse([
            'data' => $applications,
            'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
        ]);
    }

    private function show(int $id): void
    {
        $user = requireLogin();
        $application = Application::find($id);

        if (!$application) {
            jsonResponse(['error' => 'Application not found'], 404);
        }

        // Student: can only see their own
        if ($user['role'] === 'student' && (int)$application['user_id'] !== (int)$user['id']) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        // Organization: can only see applications for their scholarships
        if ($user['role'] === 'organization' && (int)$application['organization_id'] !== (int)$user['id']) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        jsonResponse(['data' => $application]);
    }

    private function create(): void
    {
        $user = requireLogin();

        if ($user['role'] !== 'student') {
            jsonResponse(['error' => 'Only students can apply to scholarships'], 403);
        }

        $input = $this->getInput();
        $scholarshipId = (int)($input['scholarship_id'] ?? 0);

        if ($scholarshipId <= 0) {
            jsonResponse(['error' => 'Scholarship ID is required'], 422);
        }

        $application = Application::create((int)$user['id'], $scholarshipId);

        if (!$application) {
            jsonResponse(['error' => 'Unable to create application'], 400);
        }

        AuditLog::create((int)$user['id'], 'application_created', 'applications', (int)$application['id']);

        jsonResponse(['message' => 'Application created', 'data' => $application], 201);
    }

    private function updateStatus(int $id): void
    {
        $user = requireLogin();
        $input = $this->getInput();

        $application = Application::find($id);
        if (!$application) {
            jsonResponse(['error' => 'Application not found'], 404);
        }

        $newStatus = $input['status'] ?? '';

        // Student can only withdraw their own application
        if ($user['role'] === 'student') {
            if ((int)$application['user_id'] !== (int)$user['id']) {
                jsonResponse(['error' => 'Access denied'], 403);
            }
            if ($newStatus === 'withdrawn') {
                Application::updateStatus($id, 'withdrawn', null);
                AuditLog::create((int)$user['id'], 'application_withdrawn', 'applications', $id);
                jsonResponse(['message' => 'Application withdrawn']);
            } else {
                jsonResponse(['error' => 'Students can only withdraw applications'], 403);
            }
        }

        // Organization can update review status
        if ($user['role'] === 'organization') {
            if ((int)$application['organization_id'] !== (int)$user['id']) {
                jsonResponse(['error' => 'Access denied'], 403);
            }

            $validStatuses = ['started', 'under_review', 'approved', 'declined', 'withdrawn'];
            if (!in_array($newStatus, $validStatuses, true)) {
                jsonResponse(['error' => 'Invalid status'], 422);
            }

            Application::updateStatus($id, $newStatus, (int)$user['id']);
            AuditLog::create((int)$user['id'], 'application_status_updated', 'applications', $id, [
                'status' => $newStatus,
                'notes'  => $input['notes'] ?? null,
            ]);

            jsonResponse(['message' => 'Application status updated']);
        }
    }

    private function withdraw(int $id): void
    {
        $user = requireLogin();
        $application = Application::find($id);

        if (!$application) {
            jsonResponse(['error' => 'Application not found'], 404);
        }

        if ((int)$application['user_id'] !== (int)$user['id']) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        Application::updateStatus($id, 'withdrawn');
        AuditLog::create((int)$user['id'], 'application_withdrawn', 'applications', $id);
        jsonResponse(['message' => 'Application withdrawn']);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
