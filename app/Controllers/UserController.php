<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\User;

final class UserController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('users.manage', $request);
        $this->view('settings/users', [
            'title' => 'Gebruikers',
            'users' => $this->users->allUsers(),
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('users.manage', $request);
        $this->verifyCsrf($request);

        $this->validate($request, [
            'name'     => 'required|max:120',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:10',
            'role'     => 'required|in:admin,manager,sales',
        ], ['name' => 'Naam', 'email' => 'E-mail', 'password' => 'Wachtwoord']);

        $name = (string) $request->input('name');
        $id = $this->users->create([
            'name'          => $name,
            'email'         => strtolower(trim((string) $request->input('email'))),
            'password_hash' => User::hashPassword((string) $request->input('password')),
            'role'          => (string) $request->input('role'),
            'phone'         => $request->input('phone'),
            'job_title'     => $request->input('job_title'),
            'booking_slug'  => $this->users->uniqueSlug($name),
            'status'        => 'active',
        ]);
        AuditLog::record('user.created', 'user', $id, ['role' => $request->input('role')]);

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        Session::flash('success', 'Gebruiker toegevoegd.');
        $this->redirect('/settings/users');
    }

    public function update(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('users.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        $data = [
            'name'      => (string) $request->input('name'),
            'role'      => in_array($request->input('role'), ['admin', 'manager', 'sales'], true) ? $request->input('role') : 'sales',
            'phone'     => $request->input('phone'),
            'job_title' => $request->input('job_title'),
            'status'    => in_array($request->input('status'), ['active', 'suspended'], true) ? $request->input('status') : 'active',
        ];
        if ($pass = $request->input('password')) {
            if (strlen((string) $pass) >= 10) {
                $data['password_hash'] = User::hashPassword((string) $pass);
            }
        }
        $this->users->update($id, $data);
        AuditLog::record('user.updated', 'user', $id);

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        Session::flash('success', 'Gebruiker bijgewerkt.');
        $this->redirect('/settings/users');
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('users.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        if ($id === (int) Auth::id()) {
            $this->json(['error' => 'Je kunt je eigen account niet verwijderen.'], 422);
        }
        // Soft-suspend rather than hard delete to preserve history/foreign keys.
        $this->users->update($id, ['status' => 'suspended']);
        AuditLog::record('user.suspended', 'user', $id);

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        Session::flash('success', 'Gebruiker gedeactiveerd.');
        $this->redirect('/settings/users');
    }
}
