<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Activity;
use App\Models\Contact;

final class ContactController extends Controller
{
    private Contact $contacts;

    public function __construct()
    {
        $this->contacts = new Contact();
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('contacts.manage', $request);
        $page = (int) $request->query('page', '1');
        $search = trim((string) $request->query('q', ''));

        $this->view('contacts/index', [
            'title'  => 'Contacten',
            'result' => $this->contacts->listing($page, 25, $search),
            'search' => $search,
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('contacts.manage', $request);
        $this->verifyCsrf($request);

        $this->validate($request, [
            'customer_id' => 'required|integer',
            'first_name'  => 'required|max:80',
            'email'       => 'email',
        ], ['first_name' => 'Voornaam']);

        $data = $request->only(['customer_id', 'first_name', 'last_name', 'job_title', 'email', 'phone', 'mobile', 'linkedin', 'is_primary']);
        $data['is_primary'] = (int) (bool) ($data['is_primary'] ?? 0);

        if ($data['is_primary']) {
            $this->contacts->unsetPrimary((int) $data['customer_id']);
        }

        $id = $this->contacts->create($data);
        (new Activity())->log([
            'customer_id' => (int) $data['customer_id'], 'contact_id' => $id, 'user_id' => Auth::id(),
            'type' => 'system', 'subject' => 'Contact toegevoegd: ' . trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
        ]);
        AuditLog::record('contact.created', 'contact', $id);

        if ($request->wantsJson()) {
            $this->json(['ok' => true, 'redirect' => '/customers/' . (int) $data['customer_id']]);
        }
        Session::flash('success', 'Contact toegevoegd.');
        $this->redirect('/customers/' . (int) $data['customer_id']);
    }

    public function update(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('contacts.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        $contact = $this->contacts->find($id);
        if ($contact === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }

        $data = $request->only(['first_name', 'last_name', 'job_title', 'email', 'phone', 'mobile', 'linkedin', 'is_primary']);
        if (!empty($data['is_primary'])) {
            $this->contacts->unsetPrimary((int) $contact['customer_id']);
            $data['is_primary'] = 1;
        }
        $this->contacts->update($id, $data);
        AuditLog::record('contact.updated', 'contact', $id);

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        $this->redirect('/customers/' . (int) $contact['customer_id']);
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('contacts.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $contact = $this->contacts->find($id);
        $this->contacts->delete($id);
        AuditLog::record('contact.deleted', 'contact', $id);

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        $this->redirect('/customers/' . (int) ($contact['customer_id'] ?? 0));
    }
}
