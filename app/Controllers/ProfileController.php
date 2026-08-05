<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Totp;
use App\Models\User;

final class ProfileController extends Controller
{
    public function show(Request $request): never
    {
        $this->requireAuth($request);
        $user = Auth::user();
        // Provisional 2FA secret for enabling (stored in session until confirmed).
        $secret = Session::get('2fa_setup') ?? Totp::generateSecret();
        Session::set('2fa_setup', $secret);
        $otpUri = Totp::otpauthUri($secret, $user['email'], (string) config('app.name', 'SalesFlow'));

        $this->view('settings/profile', [
            'title'   => 'Mijn profiel',
            'user'    => $user,
            'secret'  => $secret,
            'otpUri'  => $otpUri,
        ]);
    }

    public function update(Request $request): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $data = $this->validate($request, [
            'name'  => 'required|max:120',
            'email' => 'required|email',
        ], ['name' => 'Naam', 'email' => 'E-mail']);

        (new User())->update((int) Auth::id(), [
            'name'      => $data['name'],
            'email'     => strtolower(trim((string) $data['email'])),
            'phone'     => $request->input('phone'),
            'job_title' => $request->input('job_title'),
            'signature' => $request->input('signature'),
            'theme'     => in_array($request->input('theme'), ['light', 'dark', 'system'], true) ? $request->input('theme') : 'system',
            'daily_call_goal' => max(0, (int) $request->input('daily_call_goal', 40)),
        ]);
        AuditLog::record('profile.updated', 'user', (int) Auth::id());
        Session::flash('success', 'Profiel bijgewerkt.');
        $this->redirect('/profile');
    }

    public function updatePassword(Request $request): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $user = Auth::user();
        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('password', '');

        if (!password_verify($current, $user['password_hash'])) {
            Session::flash('error', 'Huidig wachtwoord is onjuist.');
            $this->redirect('/profile');
        }
        if (strlen($new) < 10 || $new !== $request->input('password_confirmation')) {
            Session::flash('error', 'Nieuw wachtwoord is te kort of komt niet overeen.');
            $this->redirect('/profile');
        }

        (new User())->update((int) $user['id'], ['password_hash' => User::hashPassword($new)]);
        AuditLog::record('password.changed', 'user', (int) $user['id']);

        // Handle optional 2FA toggle.
        $twoFactor = $request->input('two_factor_code');
        if ($twoFactor) {
            $secret = (string) Session::get('2fa_setup', '');
            if ($secret && Totp::verify($secret, (string) $twoFactor)) {
                (new User())->update((int) $user['id'], ['two_factor_secret' => $secret, 'two_factor_enabled' => 1]);
                Session::remove('2fa_setup');
                Session::flash('success', 'Wachtwoord gewijzigd en 2FA ingeschakeld.');
                $this->redirect('/profile');
            }
        }

        Session::flash('success', 'Wachtwoord gewijzigd.');
        $this->redirect('/profile');
    }
}
