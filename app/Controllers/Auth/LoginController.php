<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;

final class LoginController extends Controller
{
    public function show(Request $request): never
    {
        $this->view('auth/login', ['title' => 'Aanmelden'], 'layouts/auth');
    }

    public function login(Request $request): never
    {
        $this->verifyCsrf($request);

        $data = $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ], ['email' => 'E-mailadres', 'password' => 'Wachtwoord']);

        $email = strtolower(trim((string) $data['email']));
        $throttleKey = 'login:' . $email . ':' . $request->ip();
        $max = (int) config('security.max_login_attempts', 5);

        if (!RateLimiter::attempt($throttleKey, $max, (int) config('security.lockout_seconds', 900))) {
            Session::flash('error', 'Te veel pogingen. Probeer het over enkele minuten opnieuw.');
            AuditLog::record('login.throttled', 'user', null, ['email' => $email]);
            $this->back();
        }

        $remember = (bool) $request->input('remember');

        if (!Auth::attempt($email, (string) $data['password'], false)) {
            AuditLog::record('login.failed', 'user', null, ['email' => $email]);
            Session::flash('error', 'Onjuiste inloggegevens.');
            Session::flash('old', ['email' => $email]);
            $this->back();
        }

        RateLimiter::clear($throttleKey);
        $user = Auth::user();

        // Two-factor gate.
        if (!empty($user['two_factor_enabled'])) {
            Auth::logout();
            Session::set('2fa_user', (int) $user['id']);
            Session::set('2fa_remember', $remember);
            $this->redirect('/2fa');
        }

        if ($remember) {
            // Re-issue with remember token now that credentials are verified.
            Auth::login($user, true);
        }

        AuditLog::record('login.success', 'user', (int) $user['id']);
        $intended = Session::flash('intended');
        $this->redirect(is_string($intended) && $intended !== '/login' ? $intended : '/dashboard');
    }

    public function logout(Request $request): never
    {
        $this->verifyCsrf($request);
        AuditLog::record('logout', 'user', Auth::id());
        Auth::logout();
        Session::flash('success', 'Je bent afgemeld.');
        $this->redirect('/login');
    }
}
