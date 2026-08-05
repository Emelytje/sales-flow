<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;
use App\Models\Activity;

final class EmailController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('email.send', $request);
        $db = Database::instance();

        $messages = $db->all(
            "SELECT m.*, c.company_name FROM email_messages m
             LEFT JOIN customers c ON c.id = m.customer_id
             WHERE m.user_id = ? ORDER BY m.created_at DESC LIMIT 100",
            [Auth::id()]
        );
        $templates = $db->all('SELECT * FROM email_templates ORDER BY name');

        $this->view('emails/index', [
            'title'     => 'E-mail',
            'messages'  => $messages,
            'templates' => $templates,
            'customers' => $db->all('SELECT id, company_name, email FROM customers WHERE email IS NOT NULL AND email <> "" ORDER BY company_name LIMIT 1000'),
        ]);
    }

    /** Send a tracked email via SMTP (free) and log it. */
    public function send(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('email.send', $request);
        $this->verifyCsrf($request);

        $data = $this->validate($request, [
            'to_email' => 'required|email',
            'subject'  => 'required|max:255',
            'body'     => 'required',
        ], ['to_email' => 'Ontvanger', 'subject' => 'Onderwerp', 'body' => 'Bericht']);

        $db = Database::instance();
        $token = bin2hex(random_bytes(20));
        $customerId = $request->input('customer_id') ?: null;

        $user = Auth::user();
        $signature = $user['signature'] ? '<br><br>--<br>' . nl2br(e($user['signature'])) : '';
        $html = $this->wrapBody((string) $data['body'], $signature, $token);

        $ok = Mailer::send((string) $data['to_email'], (string) $data['subject'], $html);

        $id = $db->insert('email_messages', [
            'user_id'        => Auth::id(),
            'customer_id'    => $customerId,
            'contact_id'     => $request->input('contact_id') ?: null,
            'direction'      => 'outbound',
            'to_email'       => (string) $data['to_email'],
            'from_email'     => (string) config('mail.from_email'),
            'subject'        => (string) $data['subject'],
            'body'           => $html,
            'status'         => $ok ? 'sent' : 'failed',
            'tracking_token' => $token,
            'sent_at'        => $ok ? date('Y-m-d H:i:s') : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($customerId) {
            (new Activity())->log([
                'customer_id' => (int) $customerId, 'user_id' => Auth::id(), 'type' => 'email',
                'subject' => 'E-mail verzonden: ' . $data['subject'],
            ]);
        }
        AuditLog::record('email.sent', 'email', $id, ['to' => $data['to_email']]);

        if ($request->wantsJson()) {
            $this->json(['ok' => $ok, 'id' => $id]);
        }
        Session::flash($ok ? 'success' : 'error', $ok ? 'E-mail verzonden.' : 'Verzenden mislukt (controleer SMTP).');
        $this->redirect('/emails');
    }

    public function storeTemplate(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('email.send', $request);
        $this->verifyCsrf($request);
        $this->validate($request, ['name' => 'required|max:160', 'subject' => 'required|max:255', 'body' => 'required'], ['name' => 'Naam']);
        $id = Database::instance()->insert('email_templates', [
            'project_id' => $request->input('project_id') ?: null,
            'name'       => (string) $request->input('name'),
            'subject'    => (string) $request->input('subject'),
            'body'       => (string) $request->input('body'),
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function deleteTemplate(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('email.send', $request);
        $this->verifyCsrf($request);
        Database::instance()->delete('email_templates', ['id' => (int) $params['id']]);
        $this->json(['ok' => true]);
    }

    /** Wrap the body with a tracking pixel and rewrite links for click tracking. */
    private function wrapBody(string $body, string $signature, string $token): string
    {
        $base = rtrim((string) config('app.url'), '/');
        // Rewrite absolute links to go through the click tracker.
        $body = preg_replace_callback('/href="(https?:\/\/[^"]+)"/i', static function ($m) use ($base, $token) {
            return 'href="' . $base . '/t/c/' . $token . '?u=' . urlencode($m[1]) . '"';
        }, $body) ?? $body;

        $pixel = '<img src="' . $base . '/t/o/' . $token . '" width="1" height="1" alt="" style="display:none">';
        return '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:15px;color:#2C2230;line-height:1.6;">'
            . nl2br($body) . $signature . '</div>' . $pixel;
    }
}
