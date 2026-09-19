<?php

declare(strict_types=1);

class AuthApi
{
    private $csrf;

    public function __construct()
    {
        $this->csrf = new Csrf(SessionManager::getInstance());
    }

    public function route(string $method, string $action, string $id): void
    {
        switch ($action) {
            case 'register':
                $this->register();
                break;
            case 'login':
                $this->login();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'forgot-password':
                if ($method === 'POST') $this->sendResetEmail();
                break;
            case 'reset-password':
                if ($method === 'POST') $this->resetPassword();
                break;
            case 'me':
                $this->me();
                break;
            default:
                jsonResponse(['error' => 'Endpoint not found'], 404);
        }
    }

    public function register(): void
    {
        $input = $this->getInput();

        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'student';
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');

        if (!$email) {
            jsonResponse(['error' => 'Valid email is required'], 422);
        }
        if (!validatePassword($password)) {
            jsonResponse(['error' => 'Password must be at least 8 characters'], 422);
        }
        if (empty($firstName) || empty($lastName)) {
            jsonResponse(['error' => 'First and last name are required'], 422);
        }

        $existing = User::findByEmail($email);
        if ($existing) {
            jsonResponse(['error' => 'An account with this email already exists'], 409);
        }

        $validRoles = ['student', 'organization'];
        if (!in_array($role, $validRoles, true)) {
            $role = 'student';
        }

        $userData = [
            'uuid'              => generateUuid(),
            'email'             => $email,
            'password'          => $password,
            'role'              => $role,
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'school'            => $input['school'] ?? null,
            'graduation_year'   => $input['graduation_year'] ?? null,
            'organization_name' => $input['organization_name'] ?? null,
            'phone'             => $input['phone'] ?? null,
            'email_verified'    => 0,
            'is_active'         => 1,
        ];

        $user = User::create($userData);
        unset($user['password_hash']);

        AuditLog::create((int)$user['id'], 'user_registered', 'users', (int)$user['id'], [
            'role' => $role,
        ]);

        jsonResponse(['message' => 'Registration successful', 'user' => $user], 201);
    }

    public function login(): void
    {
        $input = $this->getInput();

        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';

        if (!$email) {
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $user = User::findByEmail($email);

        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if (!$user['is_active']) {
            jsonResponse(['error' => 'Account is deactivated'], 403);
        }

        $session = SessionManager::getInstance();
        $session->login((int)$user['id'], $user['role']);
        User::updateLastLogin((int)$user['id']);

        AuditLog::create((int)$user['id'], 'user_login', 'users', (int)$user['id']);

        unset($user['password_hash']);

        jsonResponse(['message' => 'Login successful', 'user' => $user, 'csrf_token' => $this->csrf->getToken()]);
    }

    public function logout(): void
    {
        $session = SessionManager::getInstance();
        $userId = $session->getUserId();
        if ($userId) {
            AuditLog::create((int)$userId, 'user_logout', 'users', (int)$userId);
        }
        $session->destroy();
        jsonResponse(['message' => 'Logged out successfully']);
    }

    public function sendResetEmail(): void
    {
        $input = $this->getInput();
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            jsonResponse(['error' => 'If an account exists with that email, a reset link has been sent']);
        }

        $user = User::findByEmail($email);

        if ($user) {
            $token = User::createPasswordResetToken((int)$user['id']);
            AuditLog::create((int)$user['id'], 'password_reset_requested', 'users', (int)$user['id']);
            // In production: send email via PHPMailer/Symfony Mailer
            // mail($email, 'Password Reset', "Reset token: $token\nExpires in 1 hour.");
        }

        // Generic message to prevent account enumeration
        jsonResponse(['message' => 'If an account exists with that email, a reset link has been sent']);
    }

    public function resetPassword(): void
    {
        $input = $this->getInput();
        $token = $input['token'] ?? '';
        $newPassword = $input['password'] ?? '';

        if (empty($token)) {
            jsonResponse(['error' => 'Token is required'], 422);
        }

        if (!validatePassword($newPassword)) {
            jsonResponse(['error' => 'Password must be at least 8 characters'], 422);
        }

        $userId = User::validatePasswordResetToken($token);

        if (!$userId) {
            jsonResponse(['error' => 'Invalid or expired token'], 400);
        }

        User::update($userId, ['password' => $newPassword]);
        User::consumePasswordResetToken($token);

        AuditLog::create($userId, 'password_reset_completed', 'users', $userId);

        jsonResponse(['message' => 'Password has been reset successfully']);
    }

    public function me(): void
    {
        $user = AuthMiddleware::handle();
        if (!$user) {
            jsonResponse(['error' => 'Not authenticated'], 401);
        }
        unset($user['password_hash']);
        jsonResponse(['user' => $user]);
    }

    private function getInput(): array
    {
        $content = file_get_contents('php://input');
        $json = json_decode($content, true);
        if (is_array($json)) {
            return $json;
        }
        return $_POST;
    }
}
