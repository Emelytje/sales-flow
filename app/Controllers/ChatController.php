<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\FileService;
use App\Core\Request;

final class ChatController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $userId = (int) Auth::id();
        $db = Database::instance();

        $channels = $this->channelsFor($userId);
        $team = $db->all('SELECT id, name, color FROM users WHERE status = "active" AND id <> ? ORDER BY name', [$userId]);

        $this->view('chat/index', [
            'title'    => 'Chat',
            'channels' => $channels,
            'team'     => $team,
            'me'       => $userId,
        ]);
    }

    /** JSON: messages for a channel (optionally only newer than ?after=id). */
    public function messages(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $channelId = (int) $params['id'];
        $userId = (int) Auth::id();
        if (!$this->isMember($channelId, $userId)) {
            $this->json(['error' => 'Geen toegang'], 403);
        }
        $after = (int) $request->query('after', '0');
        $db = Database::instance();

        $rows = $db->all(
            "SELECT m.*, u.name AS user_name, u.color AS user_color
             FROM chat_messages m JOIN users u ON u.id = m.user_id
             WHERE m.channel_id = ? AND m.id > ? ORDER BY m.id ASC LIMIT 200",
            [$channelId, $after]
        );

        // Mark read + who's typing (active in last 6s, excluding me).
        $db->run('UPDATE chat_members SET last_read_at = ? WHERE channel_id = ? AND user_id = ?', [date('Y-m-d H:i:s'), $channelId, $userId]);
        $typing = $db->all(
            "SELECT u.name FROM chat_members cm JOIN users u ON u.id = cm.user_id
             WHERE cm.channel_id = ? AND cm.user_id <> ? AND cm.typing_at > (NOW() - INTERVAL 6 SECOND)",
            [$channelId, $userId]
        );

        $this->json([
            'messages' => array_map(static fn ($m) => [
                'id'     => (int) $m['id'],
                'user'   => $m['user_name'],
                'color'  => $m['user_color'],
                'mine'   => (int) $m['user_id'] === $userId,
                'body'   => $m['body'],
                'attachment' => $m['attachment'],
                'at'     => $m['created_at'],
            ], $rows),
            'typing'   => array_map(static fn ($t) => $t['name'], $typing),
        ]);
    }

    public function send(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $channelId = (int) $params['id'];
        $userId = (int) Auth::id();
        if (!$this->isMember($channelId, $userId)) {
            $this->json(['error' => 'Geen toegang'], 403);
        }

        $body = trim((string) $request->input('body', ''));
        $attachment = null;
        if (($file = $request->file('file')) !== null) {
            try {
                $meta = FileService::store($file, 'project', $channelId); // stored under chat scope
                $attachment = $meta['stored_name'];
            } catch (\Throwable $e) {
                $this->json(['error' => $e->getMessage()], 422);
            }
        }
        if ($body === '' && $attachment === null) {
            $this->json(['error' => 'Leeg bericht'], 422);
        }

        $db = Database::instance();
        $id = $db->insert('chat_messages', [
            'channel_id' => $channelId,
            'user_id'    => $userId,
            'body'       => $body,
            'attachment' => $attachment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->run('UPDATE chat_members SET typing_at = NULL WHERE channel_id = ? AND user_id = ?', [$channelId, $userId]);

        // Notify other members.
        foreach ($db->all('SELECT user_id FROM chat_members WHERE channel_id = ? AND user_id <> ?', [$channelId, $userId]) as $m) {
            \App\Services\NotificationService::notify((int) $m['user_id'], 'chat', 'Nieuw bericht van ' . Auth::user()['name'], mb_substr($body, 0, 80), '/chat?c=' . $channelId, 'message');
        }

        $this->json(['ok' => true, 'id' => $id]);
    }

    /** Stream a chat file attachment, checking channel membership. */
    public function file(Request $request): never
    {
        $this->requireAuth($request);
        $messageId = (int) $request->query('m', '0');
        $db = Database::instance();
        $msg = $db->first('SELECT * FROM chat_messages WHERE id = ?', [$messageId]);
        if ($msg === null || empty($msg['attachment']) || !$this->isMember((int) $msg['channel_id'], (int) Auth::id())) {
            http_response_code(404);
            exit;
        }
        $path = FileService::absolutePath($msg['attachment']);
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode(basename($msg['attachment'])) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function typing(Request $request, array $params): never
    {
        $this->requireAuth($request);
        Database::instance()->run(
            'UPDATE chat_members SET typing_at = ? WHERE channel_id = ? AND user_id = ?',
            [date('Y-m-d H:i:s'), (int) $params['id'], (int) Auth::id()]
        );
        $this->json(['ok' => true]);
    }

    /** Create a group channel or open a 1:1 DM (find-or-create). */
    public function create(Request $request): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $userId = (int) Auth::id();
        $db = Database::instance();

        $withUser = (int) $request->input('user_id', '0');
        if ($withUser > 0) {
            // Existing DM?
            $existing = $db->first(
                "SELECT c.id FROM chat_channels c
                 JOIN chat_members m1 ON m1.channel_id = c.id AND m1.user_id = ?
                 JOIN chat_members m2 ON m2.channel_id = c.id AND m2.user_id = ?
                 WHERE c.is_direct = 1 LIMIT 1",
                [$userId, $withUser]
            );
            if ($existing) {
                $this->json(['ok' => true, 'id' => (int) $existing['id']]);
            }
            $id = $db->insert('chat_channels', ['is_direct' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            $db->insert('chat_members', ['channel_id' => $id, 'user_id' => $userId]);
            $db->insert('chat_members', ['channel_id' => $id, 'user_id' => $withUser]);
            $this->json(['ok' => true, 'id' => $id]);
        }

        // Group channel.
        $name = trim((string) $request->input('name', 'Kanaal'));
        $id = $db->insert('chat_channels', ['name' => $name, 'is_direct' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        $db->insert('chat_members', ['channel_id' => $id, 'user_id' => $userId]);
        // Add all active users to group channels by default.
        foreach ($db->all('SELECT id FROM users WHERE status = "active" AND id <> ?', [$userId]) as $u) {
            $db->insert('chat_members', ['channel_id' => $id, 'user_id' => (int) $u['id']]);
        }
        $this->json(['ok' => true, 'id' => $id]);
    }

    private function channelsFor(int $userId): array
    {
        return Database::instance()->all(
            "SELECT c.id, c.name, c.is_direct,
                    (SELECT u.name FROM chat_members cm2 JOIN users u ON u.id = cm2.user_id
                     WHERE cm2.channel_id = c.id AND cm2.user_id <> ? LIMIT 1) AS other_name,
                    (SELECT u.color FROM chat_members cm3 JOIN users u ON u.id = cm3.user_id
                     WHERE cm3.channel_id = c.id AND cm3.user_id <> ? LIMIT 1) AS other_color,
                    (SELECT body FROM chat_messages m WHERE m.channel_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT created_at FROM chat_messages m WHERE m.channel_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at,
                    (SELECT COUNT(*) FROM chat_messages m WHERE m.channel_id = c.id AND m.created_at > COALESCE(cm.last_read_at,'2000-01-01') AND m.user_id <> ?) AS unread
             FROM chat_channels c
             JOIN chat_members cm ON cm.channel_id = c.id AND cm.user_id = ?
             ORDER BY last_at DESC",
            [$userId, $userId, $userId, $userId]
        );
    }

    private function isMember(int $channelId, int $userId): bool
    {
        return (int) Database::instance()->scalar(
            'SELECT COUNT(*) FROM chat_members WHERE channel_id = ? AND user_id = ?',
            [$channelId, $userId]
        ) > 0;
    }
}
