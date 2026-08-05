<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Core\Totp;

final class TwoFactorController extends Controller
{
    public function show(Request $request): never
    {
        if (!Session::has('2fa_user')) {
            $this->redirect('/login');
        }
        $this->view('auth/twofactor', ['title' => 'Verificatie'], 'layouts/auth');
    }

    public function verify(Request $request): never
    {
        $this->verifyCsrf($request);
        $userId = Session::get('2fa_user');
        if ($userId === null) {
            $this->redirect('/login');
        }

        if (!RateLimiter::attempt('2fa:' . $userId, 6, 300)) {
            Session::flash('error', 'Te veel pogingen. Wacht enkele minuten.');
            $this->back();
        }

        $code = (string) $request->input('code', '');
        $user = Database::instance()->first('SELECT * FROM users WHERE id = ?', [$userId]);

        if ($user === null || !Totp::verify((string) $user['two_factor_secret'], $code)) {
            AuditLog::record('2fa.failed', 'user', (int) $userId);
            Session::flash('error', 'Ongeldige verificatiecode.');
            $this->back();
        }

        RateLimiter::clear('2fa:' . $userId);
        $remember = (bool) Session::get('2fa_remember');
        Session::remove('2fa_user');
        Session::remove('2fa_remember');

        Auth::login($user, $remember);
        AuditLog::record('login.success', 'user', (int) $user['id'], ['2fa' => true]);
        $this->redirect('/dashboard');
    }
}
