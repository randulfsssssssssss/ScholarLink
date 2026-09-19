<?php

declare(strict_types=1);

class ProfileApi
{
    public function route(string $method, string $action, string $id): void
    {
        $user = requireLogin();

        switch ($action) {
            case '':
                if ($method === 'GET') $this->show();
                elseif ($method === 'PUT') $this->update();
                break;
            case 'change-password':
                if ($method === 'POST') $this->changePassword();
                break;
            case 'completion':
                if ($method === 'GET') $this->completion();
                break;
            default:
                jsonResponse(['error' => 'Endpoint not found'], 404);
                break;
        }
    }

    private function show(): void
    {
        $user = getUserFromRequest();
        unset($user['password_hash']);
        unset($user['uuid']);
        jsonResponse(['data' => $user]);
    }

    private function update(): void
    {
        $user = getUserFromRequest();
        $input = $this->getInput();

        $updateData = [];
        $allowedFields = [
            'first_name', 'last_name', 'school', 'graduation_year',
            'organization_name', 'phone', 'profile_complete',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            User::update((int)$user['id'], $updateData);
        }

        AuditLog::create((int)$user['id'], 'profile_updated', 'users', (int)$user['id']);
        jsonResponse(['message' => 'Profile updated', 'data' => User::find((int)$user['id'])]);
    }

    private function changePassword(): void
    {
        $user = getUserFromRequest();
        $input = $this->getInput();

        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        $fullUser = User::findByEmail($user['email']);
        if (!$fullUser || !User::verifyPassword($currentPassword, $fullUser['password_hash'])) {
            jsonResponse(['error' => 'Current password is incorrect'], 401);
        }

        if (!validatePassword($newPassword)) {
            jsonResponse(['error' => 'New password must be at least 8 characters'], 422);
        }

        User::update((int)$user['id'], ['password' => $newPassword]);

        AuditLog::create((int)$user['id'], 'password_changed', 'users', (int)$user['id']);
        jsonResponse(['message' => 'Password changed successfully']);
    }

    private function completion(): void
    {
        $user = getUserFromRequest();
        $fullUser = User::find((int)$user['id']);

        $fields = ['first_name', 'last_name', 'email', 'phone'];
        if ($user['role'] === 'student') {
            $fields[] = 'school';
            $fields[] = 'graduation_year';
        } elseif ($user['role'] === 'organization') {
            $fields[] = 'organization_name';
        }

        $completed = 0;
        $total = count($fields);

        foreach ($fields as $field) {
            if (!empty($fullUser[$field])) {
                $completed++;
            }
        }

        $percentage = $total > 0 ? (int)(($completed / $total) * 100) : 0;

        jsonResponse([
            'data' => [
                'completed_fields' => $completed,
                'total_fields'     => $total,
                'percentage'       => $percentage,
                'fields'           => $fields,
            ],
        ]);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        return is_array($json) ? $json : $_POST;
    }
}
