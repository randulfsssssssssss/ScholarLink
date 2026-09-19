<?php

declare(strict_types=1);

class AuthMiddleware
{
    public static function handle(): ?User
    {
        $session = SessionManager::getInstance();
        if (!$session->isLoggedIn()) {
            return null;
        }
        $userId = $session->get('user_id');
        $user = User::find((int)$userId);
        if (!$user) {
            $session->destroy();
            return null;
        }
        if (!$user['is_active']) {
            return null;
        }
        return $user;
    }
}
