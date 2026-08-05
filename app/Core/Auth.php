<?php
/**
 * Authentication & authorization service.
 *
 * Handles credential verification, "remember me" persistent tokens, the current
 * authenticated user, role checks and fine-grained permission checks backed by
 * the role_permissions table.
 */

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    /** @var array<string, bool>|null */
    private static ?array $permissionCache = null;

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $db = Database::instance();
        $user = $db->first(
            'SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1',
            [strtolower(trim($email))]
        );

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Transparently upgrade the hash if the cost changed.
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => (int) Config::get('security.bcrypt_cost', 12)])) {
            $db->update('users', [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => (int) Config::get('security.bcrypt_cost', 12)]),
            ], ['id' => $user['id']]);
        }

        self::login($user, $remember);
        return true;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$permissionCache = null;

        Database::instance()->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        if ($remember) {
            self::issueRememberToken((int) $user['id']);
        }
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $days = (int) Config::get('session.remember_days', 30);
        $expires = date('Y-m-d H:i:s', time() + $days * 86400);

        Database::instance()->insert('remember_tokens', [
            'user_id'        => $userId,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at'     => $expires,
        ]);

        setcookie('remember', $selector . ':' . $validator, [
            'expires'  => time() + $days * 86400,
            'path'     => '/',
            'secure'   => (bool) Config::get('session.secure', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function loginFromCookie(): bool
    {
        if (empty($_COOKIE['remember']) || !str_contains($_COOKIE['remember'], ':')) {
            return false;
        }
        [$selector, $validator] = explode(':', $_COOKIE['remember'], 2);
        $db = Database::instance();
        $token = $db->first('SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1', [$selector]);

        if ($token === null || strtotime($token['expires_at']) < time()) {
            return false;
        }
        if (!hash_equals($token['validator_hash'], hash('sha256', $validator))) {
            return false;
        }

        $user = $db->first('SELECT * FROM users WHERE id = ? AND status = "active"', [$token['user_id']]);
        if ($user === null) {
            return false;
        }

        // Rotate the token on use.
        $db->delete('remember_tokens', ['id' => $token['id']]);
        self::login($user, true);
        return true;
    }

    public static function logout(): void
    {
        if (!empty($_COOKIE['remember']) && str_contains($_COOKIE['remember'], ':')) {
            [$selector] = explode(':', $_COOKIE['remember'], 2);
            Database::instance()->delete('remember_tokens', ['selector' => $selector]);
            setcookie('remember', '', time() - 3600, '/');
        }
        Session::destroy();
        self::$user = null;
        self::$permissionCache = null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = Session::get('user_id');
        if ($id === null) {
            if (self::loginFromCookie()) {
                return self::$user;
            }
            return null;
        }
        self::$user = Database::instance()->first(
            'SELECT * FROM users WHERE id = ? AND status = "active"',
            [$id]
        );
        return self::$user;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Fine-grained permission check. Admins implicitly have every permission.
     */
    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isAdmin()) {
            return true;
        }

        if (self::$permissionCache === null) {
            self::$permissionCache = [];
            $rows = Database::instance()->all(
                'SELECT p.slug FROM role_permissions rp
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE rp.role = ?',
                [self::role()]
            );
            foreach ($rows as $row) {
                self::$permissionCache[$row['slug']] = true;
            }
        }
        return isset(self::$permissionCache[$permission]);
    }
}
