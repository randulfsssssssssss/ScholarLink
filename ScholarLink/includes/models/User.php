<?php

declare(strict_types=1);

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        return $db->fetch('SELECT id, uuid, email, role, first_name, last_name, school, graduation_year, organization_name, phone, profile_complete, is_active, email_verified, created_at, updated_at, last_login FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getInstance();
        return $db->fetch('SELECT id, uuid, email, role, first_name, last_name, school, graduation_year, organization_name, phone, profile_complete, is_active, email_verified, created_at, updated_at, last_login, password_hash FROM users WHERE email = ?', [$email]);
    }

    public static function findByUuid(string $uuid): ?array
    {
        $db = Database::getInstance();
        return $db->fetch('SELECT id, uuid, email, role, first_name, last_name, school, graduation_year, organization_name, phone, profile_complete, is_active, email_verified, created_at, updated_at, last_login FROM users WHERE uuid = ?', [$uuid]);
    }

    public static function create(array $data): array
    {
        $db = Database::getInstance();
        $passwordHash = password_hash($data['password'], PASSWORD_ARGON2ID);
        $uuid = generateUuid();

        $db->getConnection()->prepare('
            INSERT INTO users
                (uuid, email, password_hash, role, first_name, last_name, school, graduation_year, organization_name, phone, profile_complete, email_verified)
            VALUES
                (:uuid, :email, :password_hash, :role, :first_name, :last_name, :school, :graduation_year, :organization_name, :phone, :profile_complete, :email_verified)
        ')->execute([
            'uuid'               => $uuid,
            'email'              => $data['email'],
            'password_hash'      => $passwordHash,
            'role'               => $data['role'],
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'school'             => $data['school'] ?? null,
            'graduation_year'    => $data['graduation_year'] ?? null,
            'organization_name'  => $data['organization_name'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'profile_complete'   => $data['profile_complete'] ?? 0,
            'email_verified'     => $data['email_verified'] ?? 0,
        ]);

        $id = (int)$db->lastInsertId();
        return self::find($id) ?? [];
    }

    public static function update(int $userId, array $data): bool
    {
        $db = Database::getInstance();
        $fields = [];
        $params = ['user_id' => $userId];

        $allowedFields = [
            'first_name', 'last_name', 'school', 'graduation_year',
            'organization_name', 'phone', 'profile_complete', 'is_active',
            'email_verified',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";
        $stmt = $db->getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function getAll(int $limit = 50, int $offset = 0, string $role = null): array
    {
        $db = Database::getInstance();
        $params = [$limit, $offset];
        $sql = 'SELECT id, uuid, email, role, first_name, last_name, school, graduation_year, organization_name, profile_complete, is_active, email_verified, created_at, updated_at, last_login FROM users';
        if ($role) {
            $sql .= ' WHERE role = ?';
            $params[] = $role;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        return $db->fetchAll($sql, $params);
    }

    public static function countByRole(string $role): int
    {
        $db = Database::getInstance();
        $result = $db->fetch('SELECT COUNT(*) as count FROM users WHERE role = ?', [$role]);
        return (int)($result['count'] ?? 0);
    }

    public static function delete(int $userId): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]) ||
               $db->getConnection()->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$userId]);
    }

    public static function createPasswordResetToken(int $userId): string
    {
        $db = Database::getInstance();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $db->getConnection()->prepare('
            INSERT INTO password_reset_tokens (user_id, token, expires_at, used)
            VALUES (?, ?, ?, 0)
        ')->execute([$userId, $token, $expiresAt]);

        return $token;
    }

    public static function validatePasswordResetToken(string $token): ?int
    {
        $db = Database::getInstance();
        $result = $db->fetch('
            SELECT user_id FROM password_reset_tokens
            WHERE token = ? AND used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ', [$token]);

        return $result ? (int)$result['user_id'] : null;
    }

    public static function consumePasswordResetToken(string $token): bool
    {
        $db = Database::getInstance();
        return $db->getConnection()->prepare('
            UPDATE password_reset_tokens SET used = 1 WHERE token = ?
        ')->execute([$token]) && true;
    }

    public static function updateLastLogin(int $userId): void
    {
        $db = Database::getInstance();
        $db->getConnection()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$userId]);
    }
}
