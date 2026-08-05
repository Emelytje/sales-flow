<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class WebhookApiController extends Controller
{
    public function index(Request $request): never
    {
        $this->json(['data' => Database::instance()->all('SELECT id, url, event, active, created_at FROM webhooks ORDER BY id DESC')]);
    }

    public function store(Request $request): never
    {
        $this->validate($request, [
            'url'   => 'required|url',
            'event' => 'required|max:80',
        ], ['url' => 'URL', 'event' => 'Event']);
        $id = Database::instance()->insert('webhooks', [
            'user_id'    => Auth::id(),
            'url'        => (string) $request->input('url'),
            'event'      => (string) $request->input('event'),
            'secret'     => (string) ($request->input('secret') ?: bin2hex(random_bytes(12))),
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(Database::instance()->first('SELECT * FROM webhooks WHERE id = ?', [$id]), 201);
    }

    public function destroy(Request $request, array $params): never
    {
        Database::instance()->delete('webhooks', ['id' => (int) $params['id']]);
        $this->json(['ok' => true]);
    }
}
