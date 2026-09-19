<?php

declare(strict_types=1);

class RequirementApi
{
    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case 'types':
                $this->types();
                break;
            case 'statuses':
                $this->statuses();
                break;
            default:
                if ($method === 'GET' && is_numeric($action)) {
                    $this->list((int)$action);
                } elseif ($method === 'PATCH' && is_numeric($action)) {
                    $this->updateStatus((int)$action);
                } else {
                    jsonResponse(['error' => 'Endpoint not found'], 404);
                }
                break;
        }
    }

    private function types(): void
    {
        $types = Requirement::getTypes();
        jsonResponse(['data' => $types]);
    }

    private function statuses(): void
    {
        $statuses = Requirement::getStatusLabels();
        jsonResponse(['data' => $statuses]);
    }

    private function list(int $applicationId): void
    {
        $user = requireLogin();

        if ($user['role'] === 'student') {
            if (!Application::canAccess((int)$user['id'], $applicationId)) {
                jsonResponse(['error' => 'Access denied'], 403);
            }
        } elseif ($user['role'] === 'organization') {
            $app = Application::find($applicationId);
            if (!$app || (int)$app['organization_id'] !== (int)$user['id']) {
                jsonResponse(['error' => 'Access denied'], 403);
            }
        } else {
            jsonResponse(['error' => 'Invalid role'], 403);
        }

        $requirements = Requirement::getForApplication($applicationId);
        jsonResponse(['data' => $requirements]);
    }

    private function updateStatus(int $applicationId): void
    {
        $user = requireLogin();
        $input = $this->getInput();

        $requirementId = (int)($input['requirement_id'] ?? 0);
        $status = $input['status'] ?? 'missing';

        if ($user['role'] === 'student') {
            if (!Application::canAccess((int)$user['id'], $applicationId)) {
                jsonResponse(['error' => 'Access denied'], 403);
            }
        } elseif ($user['role'] === 'organization') {
            $app = Application::find($applicationId);
            if (!$app || (int)$app['organization_id'] !== (int)$user['id']) {
                jsonResponse(['error' => 'Access denied'], 403);
            }
        } else {
            jsonResponse(['error' => 'Invalid role'], 403);
        }

        if (Requirement::updateStatus($applicationId, $requirementId, $status)) {
            if ($user['role'] === 'organization') {
                AuditLog::create((int)$user['id'], 'requirement_status_updated', 'application_requirements', null, [
                    'application_id'    => $applicationId,
                    'requirement_id'    => $requirementId,
                    'status'            => $status,
                ]);
            }
            jsonResponse(['message' => 'Requirement status updated']);
        }

        jsonResponse(['error' => 'Failed to update status'], 500);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
