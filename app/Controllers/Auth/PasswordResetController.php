<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

final class PasswordResetController extends Controller
{
    public function request(Request $request): never
    {
        $this->view('auth/forgot', ['title' => 'Wachtwoord vergeten'], 'layouts/auth');
    }

    public function sendLink(Request $request): never
    {
        $this->verifyCsrf($request);
        $data = $this->validate($request, ['email' => 'required|email'], ['email' => 'E-mailadres']);
        $email = strtolower(trim((string) $data['email']));

        if (!RateLimiter::attempt('pwreset:' . $request->ip(), 5, 900)) {
            Session::flash('error', 'Te veel aanvragen. Probeer het later opnieuw.');
            $this->back();
        }

        $user = (new User())->findByEmail($email);
        // Always show success to avoid user enumeration.
        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            $db = Database::instance();
            $db->delete('password_resets', ['email' => $email]);
            $db->insert('password_resets', [
                'email'      => $email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $link = url('reset-password/' . $token . '?email=' . urlencode($email));
            $html = View::renderPartial('emails/password_reset', [
                'name' => $user['name'],
                'link' => $link,
            ]);
            Mailer::send($email, 'Herstel je wachtwoord — SalesFlow', $html);
            AuditLog::record('password.reset_requested', 'user', (int) $user['id']);
        }

        Session::flash('success', 'Als dit e-mailadres bestaat, sturen we een herstellink.');
        $this->redirect('/login');
    }

    public function show(Request $request, array $params): never
    {
        $token = $params['token'] ?? '';
        $email = (string) $request->query('email', '');
        $this->view('auth/reset', [
            'title' => 'Nieuw wachtwoord',
            'token' => $token,
            'email' => $email,
        ], 'layouts/auth');
    }

    public function reset(Request $request): never
    {
        $this->verifyCsrf($request);
        $data = $this->validate($request, [
            'email'    => 'required|email',
            'token'    => 'required',
            'password' => 'required|min:10|confirmed',
        ], ['password' => 'Wachtwoord']);

        $email = strtolower(trim((string) $data['email']));
        $db = Database::instance();
        $row = $db->first(
            'SELECT * FROM password_resets WHERE email = ? AND token_hash = ? LIMIT 1',
            [$email, hash('sha256', (string) $data['token'])]
        );

        if ($row === null || strtotime($row['expires_at']) < time()) {
            Session::flash('error', 'De herstellink is ongeldig of verlopen.');
            $this->redirect('/forgot-password');
        }

        $user = (new User())->findByEmail($email);
        if ($user === null) {
            $this->redirect('/login');
        }

        (new User())->update((int) $user['id'], [
            'password_hash' => User::hashPassword((string) $data['password']),
        ]);
        $db->delete('password_resets', ['email' => $email]);
        // Invalidate any remember tokens.
        $db->delete('remember_tokens', ['user_id' => $user['id']]);

        AuditLog::record('password.reset', 'user', (int) $user['id']);
        Session::flash('success', 'Je wachtwoord is gewijzigd. Meld je aan.');
        $this->redirect('/login');
    }
}
