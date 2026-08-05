<?php
/**
 * Creates in-app notifications and dispatches Web Push messages when the user
 * has an active subscription.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class NotificationService
{
    public static function notify(int $userId, string $type, string $title, string $body = '', string $link = '', string $icon = 'bell'): int
    {
        $db = Database::instance();
        $id = $db->insert('notifications', [
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'link'       => $link,
            'icon'       => $icon,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Best-effort push delivery.
        try {
            WebPushService::sendToUser($userId, $title, $body, $link);
        } catch (\Throwable) {
        }

        return $id;
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::instance()->scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL',
            [$userId]
        );
    }

    public static function markAllRead(int $userId): void
    {
        Database::instance()->run(
            'UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL',
            [date('Y-m-d H:i:s'), $userId]
        );
    }
}
