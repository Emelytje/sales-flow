<?php
/**
 * Email open/click tracking. Public endpoints hit by recipients' mail clients.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class TrackingController extends Controller
{
    /** 1x1 transparent GIF pixel that records an open. */
    public function open(Request $request, array $params): never
    {
        $token = (string) ($params['token'] ?? '');
        $db = Database::instance();
        $msg = $db->first('SELECT id FROM email_messages WHERE tracking_token = ?', [$token]);
        if ($msg !== null) {
            $db->run('UPDATE email_messages SET open_count = open_count + 1, opened_at = COALESCE(opened_at, ?) WHERE id = ?', [date('Y-m-d H:i:s'), $msg['id']]);
            $db->insert('email_events', [
                'message_id' => $msg['id'], 'type' => 'open',
                'ip_address' => $request->ip(), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        // 1x1 transparent GIF.
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /** Records a click and redirects to the target URL. */
    public function click(Request $request, array $params): never
    {
        $token = (string) ($params['token'] ?? '');
        $url = (string) $request->query('u', '');
        $db = Database::instance();
        $msg = $db->first('SELECT id FROM email_messages WHERE tracking_token = ?', [$token]);
        if ($msg !== null) {
            $db->run('UPDATE email_messages SET click_count = click_count + 1 WHERE id = ?', [$msg['id']]);
            $db->insert('email_events', [
                'message_id' => $msg['id'], 'type' => 'click', 'url' => $url,
                'ip_address' => $request->ip(), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        // Only redirect to safe absolute http(s) URLs.
        if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url)) {
            $this->redirect($url);
        }
        $this->redirect('/dashboard');
    }
}
