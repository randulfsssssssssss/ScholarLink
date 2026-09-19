<?php

declare(strict_types=1);

class SessionManager
{
    private static ?SessionManager $instance = null;
    private array $config;
    private bool $started = false;

    private function __construct(array $config)
    {
        $this->config = $config;
        $this->start();
    }

    public static function getInstance(array $config = []): SessionManager
    {
        if (self::$instance === null) {
            if (empty($config)) {
                $config = (require SCHOLARLINK_ROOT . '/config/config.php')['session'];
            }
            self::$instance = new SessionManager($config);
        }
        return self::$instance;
    }

    private function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->config['name']);

        $cookieParams = [
            'lifetime'   => $this->config['timeout'],
            'path'       => '/',
            'domain'     => '',
            'secure'     => $this->config['cookie_secure'],
            'httponly'   => $this->config['cookie_httponly'],
            'samesite'   => $this->config['cookie_samesite'],
        ];
        session_set_cookie_params($cookieParams);

        session_cache_limiter('private, no-cache');
        session_start([
            'cookie_lifetime'   => $this->config['timeout'],
            'cookie_httponly'   => $this->config['cookie_httponly'],
            'cookie_secure'     => $this->config['cookie_secure'],
            'cookie_samesite'   => $this->config['cookie_samesite'],
            'use_strict_ids'    => true,
            'cache_limiter'     => 'private, no-cache',
        ]);

        $this->started = true;

        $this->checkInactivity();
    }

    private function checkInactivity(): void
    {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $now = time();

        if ($lastActivity > 0 && ($now - $lastActivity) > $this->config['timeout']) {
            $this->regenerate();
            $this->destroy();
            return;
        }

        $_SESSION['last_activity'] = $now;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): bool
    {
        if (!$this->started) {
            return false;
        }
        return session_regenerate_id(true);
    }

    public function destroy(): void
    {
        if (!$this->started) {
            return;
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        $this->started = false;
    }

    public function getUserId(): ?int
    {
        return $this->get('user_id');
    }

    public function getUserRole(): ?string
    {
        return $this->get('role');
    }

    public function isLoggedIn(): bool
    {
        return $this->has('user_id') && $this->has('role');
    }

    public function login(int $userId, string $role): void
    {
        $this->regenerate();
        $this->set('user_id', $userId);
        $this->set('role', $role);
        $this->set('last_activity', time());
    }
}
