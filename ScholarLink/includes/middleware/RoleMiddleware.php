<?php

declare(strict_types=1);

class RoleMiddleware
{
    private static array $roleHierarchy = [
        'student'      => 1,
        'organization' => 2,
        'admin'        => 3,
    ];

    public static function requireRole(string $role): void
    {
        $user = AuthMiddleware::handle();
        if (!$user) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }

        $userLevel = self::$roleHierarchy[$user['role']] ?? 0;
        $requiredLevel = self::$roleHierarchy[$role] ?? 0;

        if ($userLevel < $requiredLevel) {
            jsonResponse(['error' => 'Insufficient permissions'], 403);
        }
    }

    public static function requireRoles(array $roles): void
    {
        $user = AuthMiddleware::handle();
        if (!$user) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }

        if (!in_array($user['role'], $roles, true)) {
            jsonResponse(['error' => 'Insufficient permissions'], 403);
        }
    }

    public static function getUser(): User
    {
        $user = AuthMiddleware::handle();
        if (!$user) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }
        return $user;
    }
}
