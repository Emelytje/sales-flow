<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Models\User;

final class AuthApiController extends Controller
{
    /** Issue an API token in exchange for valid credentials. */
    public function token(Request $request): never
    {
        if (!RateLimiter::attempt('api-token:' . $request->ip(), 10, 900)) {
            $this->json(['error' => 'Te veel pogingen.'], 429);
        }
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $user = (new User())->findByEmail($email);
        if ($user === null || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $this->json(['error' => 'Ongeldige inloggegevens.'], 401);
        }

        $plain = bin2hex(random_bytes(24));
        Database::instance()->insert('api_keys', [
            'user_id'    => (int) $user['id'],
            'name'       => (string) ($request->input('name') ?: 'API-token'),
            'token_hash' => hash('sha256', $plain),
            'prefix'     => substr($plain, 0, 8),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'token'      => $plain,
            'token_type' => 'Bearer',
            'user'       => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
        ]);
    }

    public function me(Request $request): never
    {
        $u = Auth::user();
        $this->json(['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']]);
    }
}
