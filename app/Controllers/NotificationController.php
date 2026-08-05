<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;

final class NotificationController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $userId = (int) Auth::id();

        // Lightweight JSON endpoint used by the topbar poller.
        if ($request->query('json')) {
            $this->json(['unread' => NotificationService::unreadCount($userId)]);
        }

        $notifications = Database::instance()->all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100',
            [$userId]
        );
        NotificationService::markAllRead($userId);

        $this->view('notifications/index', [
            'title'         => 'Meldingen',
            'notifications' => $notifications,
        ]);
    }
}
