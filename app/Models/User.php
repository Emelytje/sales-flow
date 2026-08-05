<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Model;

final class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'name', 'email', 'password_hash', 'role', 'phone', 'job_title', 'avatar',
        'signature', 'booking_slug', 'daily_call_goal', 'color', 'theme', 'locale',
        'two_factor_secret', 'two_factor_enabled', 'status', 'last_login_at',
    ];
    protected array $searchable = ['name', 'email', 'job_title'];

    public function findByEmail(string $email): ?array
    {
        return $this->findBy(['email' => strtolower(trim($email))]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy(['booking_slug' => $slug]);
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => (int) Config::get('security.bcrypt_cost', 12)]);
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return $this->db()->all('SELECT * FROM users WHERE status = "active" ORDER BY name ASC');
    }

    public function uniqueSlug(string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?: 'rep';
        $base = trim($base, '-');
        $slug = $base;
        $i = 1;
        while ($this->findBySlug($slug) !== null) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
