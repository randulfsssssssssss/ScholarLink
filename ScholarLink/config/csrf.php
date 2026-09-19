<?php

declare(strict_types=1);

class Csrf
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function getToken(): string
    {
        $token = $this->session->get('csrf_token');
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
        }
        return $token;
    }

    public function validate(string $token): bool
    {
        $stored = $this->session->get('csrf_token');
        if (empty($stored) || empty($token)) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public function check(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $bodyToken = $_POST['csrf_token'] ?? $_POST['_token'] ?? '';
        $token = $headerToken ?: $bodyToken;

        if (!$this->validate($token)) {
            http_response_code(419);
            jsonResponse(['error' => 'CSRF token mismatch'], 419);
            exit;
        }
    }

    public function tokenField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->getToken()) . '">';
    }
}
