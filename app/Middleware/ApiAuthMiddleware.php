<?php
/**
 * Authenticates REST API requests via a Bearer API key and enforces per-key
 * rate limiting. The resolved user is placed into the session context so
 * controllers can use Auth::user() uniformly.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

final class ApiAuthMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->bearerToken();
        if ($token === null) {
            Response::json(['error' => 'Ontbrekende API-sleutel.'], 401);
        }

        $db = Database::instance();
        $key = $db->first(
            'SELECT * FROM api_keys WHERE token_hash = ? AND revoked_at IS NULL',
            [hash('sha256', $token)]
        );

        if ($key === null) {
            Response::json(['error' => 'Ongeldige API-sleutel.'], 401);
        }

        if (!RateLimiter::attempt('api:' . $key['id'], 120, 60)) {
            Response::json(['error' => 'Rate limit bereikt. Probeer later opnieuw.'], 429);
        }

        $user = $db->first('SELECT * FROM users WHERE id = ? AND status = "active"', [$key['user_id']]);
        if ($user === null) {
            Response::json(['error' => 'Gekoppelde gebruiker is inactief.'], 401);
        }

        $db->update('api_keys', ['last_used_at' => date('Y-m-d H:i:s')], ['id' => $key['id']]);
        Auth::login($user);
    }
}
