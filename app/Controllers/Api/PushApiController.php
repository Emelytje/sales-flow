<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class PushApiController extends Controller
{
    public function subscribe(Request $request): never
    {
        if (!Auth::check()) {
            $this->json(['error' => 'Niet geautoriseerd.'], 401);
        }
        $sub = $request->input('subscription');
        if (!is_array($sub) || empty($sub['endpoint']) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])) {
            $this->json(['error' => 'Ongeldige subscription.'], 422);
        }

        $db = Database::instance();
        $existing = $db->first('SELECT id FROM push_subscriptions WHERE endpoint = ?', [$sub['endpoint']]);
        if ($existing === null) {
            $db->insert('push_subscriptions', [
                'user_id'    => Auth::id(),
                'endpoint'   => $sub['endpoint'],
                'p256dh'     => $sub['keys']['p256dh'],
                'auth'       => $sub['keys']['auth'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): never
    {
        if (!Auth::check()) {
            $this->json(['error' => 'Niet geautoriseerd.'], 401);
        }
        $endpoint = (string) $request->input('endpoint', '');
        if ($endpoint !== '') {
            Database::instance()->delete('push_subscriptions', ['endpoint' => $endpoint]);
        }
        $this->json(['ok' => true]);
    }
}
