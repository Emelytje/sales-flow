<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\GoogleService;

final class OAuthController extends Controller
{
    public function googleConnect(Request $request): never
    {
        $this->requireAuth($request);
        if (!GoogleService::configured()) {
            Session::flash('error', 'Google is nog niet geconfigureerd (GOOGLE_CLIENT_ID/SECRET).');
            $this->redirect('/settings/integrations');
        }
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);
        $this->redirect(GoogleService::authUrl($state));
    }

    public function googleCallback(Request $request): never
    {
        $this->requireAuth($request);
        $state = (string) $request->query('state', '');
        if ($state === '' || !hash_equals((string) Session::get('oauth_state', ''), $state)) {
            Session::flash('error', 'Ongeldige OAuth-state. Probeer opnieuw.');
            $this->redirect('/settings/integrations');
        }
        Session::remove('oauth_state');

        if ($request->query('error')) {
            Session::flash('error', 'Google-koppeling geweigerd.');
            $this->redirect('/settings/integrations');
        }
        $code = (string) $request->query('code', '');
        if ($code === '' || !GoogleService::handleCallback((int) Auth::id(), $code)) {
            Session::flash('error', 'Google-koppeling mislukt.');
            $this->redirect('/settings/integrations');
        }
        AuditLog::record('google.connected', 'user', Auth::id());
        Session::flash('success', 'Gmail gekoppeld! Je inbox is nu beschikbaar.');
        $this->redirect('/emails/inbox');
    }

    public function googleDisconnect(Request $request): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        GoogleService::disconnect((int) Auth::id());
        AuditLog::record('google.disconnected', 'user', Auth::id());
        Session::flash('success', 'Gmail-koppeling verwijderd.');
        $this->redirect('/settings/integrations');
    }
}
