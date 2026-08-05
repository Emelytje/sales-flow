<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Note;

final class CustomerController extends Controller
{
    private Customer $customers;

    public function __construct()
    {
        $this->customers = new Customer();
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.view', $request);

        $page = (int) $request->query('page', '1');
        $search = trim((string) $request->query('q', ''));
        $filters = [
            'pipeline_stage' => $request->query('stage'),
            'status'         => $request->query('status'),
            'owner_id'       => $request->query('owner'),
            'sector_id'      => $request->query('sector'),
        ];

        $result = $this->customers->listing($page, 20, $search, $filters);

        $this->view('customers/index', [
            'title'   => 'Klanten',
            'result'  => $result,
            'search'  => $search,
            'filters' => $filters,
            'owners'  => Database::instance()->all("SELECT id, name FROM users WHERE status='active' ORDER BY name"),
            'sectors' => Database::instance()->all('SELECT id, name FROM sectors ORDER BY name'),
        ]);
    }

    public function create(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.create', $request);
        $this->view('customers/form', [
            'title'    => 'Nieuwe klant',
            'customer' => ['phone' => (string) $request->query('phone', '')],
            'lookups'  => $this->lookups(),
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.create', $request);
        $this->verifyCsrf($request);

        $data = $this->validate($request, [
            'company_name'    => 'required|max:190',
            'email'           => 'email',
            'website'         => 'max:190',
            'pipeline_stage'  => 'in:lead,contacted,qualified,proposal,won,lost',
            'estimated_value' => 'numeric',
        ], ['company_name' => 'Bedrijfsnaam']);

        $payload = $request->only([
            'company_name', 'vat_number', 'kbo_number', 'email', 'phone', 'website',
            'linkedin', 'address', 'postal_code', 'city', 'country', 'latitude',
            'longitude', 'google_place_id', 'sector_id', 'project_id', 'owner_id',
            'pipeline_stage', 'status', 'priority', 'estimated_value', 'lead_score',
        ]);
        $payload['owner_id'] = ($payload['owner_id'] ?? '') ?: Auth::id();
        $payload['created_by'] = Auth::id();
        $payload = $this->nullifyEmpty($payload, ['sector_id', 'project_id', 'owner_id', 'latitude', 'longitude']);

        $id = $this->customers->create($payload);

        // Coordinates are filled lazily by the map's geocode button or the
        // cron/geocode.php job (free OpenStreetMap), so creating stays instant.

        (new Activity())->log([
            'customer_id' => $id, 'user_id' => Auth::id(), 'type' => 'system',
            'subject' => 'Klant aangemaakt',
        ]);
        AuditLog::record('customer.created', 'customer', $id, ['name' => $payload['company_name']]);

        Session::flash('success', 'Klant aangemaakt.');
        $this->redirect('/customers/' . $id);
    }

    public function show(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.view', $request);
        $id = (int) $params['id'];

        $customer = $this->customers->findDetailed($id);
        if ($customer === null) {
            Session::flash('error', 'Klant niet gevonden.');
            $this->redirect('/customers');
        }

        $this->view('customers/show', [
            'title'    => $customer['company_name'],
            'customer' => $customer,
            'contacts' => $this->customers->contacts($id),
            'timeline' => $this->customers->timeline($id),
            'notes'    => $this->customers->notes($id),
            'mapsKey'  => (string) config('integrations.google.maps_key', ''),
        ]);
    }

    public function edit(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.edit', $request);
        $customer = $this->customers->find((int) $params['id']);
        if ($customer === null) {
            $this->redirect('/customers');
        }
        $this->view('customers/form', [
            'title'    => 'Klant bewerken',
            'customer' => $customer,
            'lookups'  => $this->lookups(),
        ]);
    }

    public function update(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.edit', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        $existing = $this->customers->find($id);
        if ($existing === null) {
            $this->redirect('/customers');
        }

        $this->validate($request, [
            'company_name'   => 'required|max:190',
            'email'          => 'email',
            'pipeline_stage' => 'in:lead,contacted,qualified,proposal,won,lost',
        ], ['company_name' => 'Bedrijfsnaam']);

        $payload = $request->only([
            'company_name', 'vat_number', 'kbo_number', 'email', 'phone', 'website',
            'linkedin', 'address', 'postal_code', 'city', 'country', 'latitude',
            'longitude', 'sector_id', 'project_id', 'owner_id', 'pipeline_stage',
            'status', 'priority', 'estimated_value', 'lead_score', 'lost_reason',
        ]);
        $payload = $this->nullifyEmpty($payload, ['sector_id', 'project_id', 'owner_id', 'latitude', 'longitude']);

        // Record a stage change on the timeline.
        if (($payload['pipeline_stage'] ?? null) && $payload['pipeline_stage'] !== $existing['pipeline_stage']) {
            (new Activity())->log([
                'customer_id' => $id, 'user_id' => Auth::id(), 'type' => 'status_change',
                'subject' => 'Fase gewijzigd naar ' . $payload['pipeline_stage'],
            ]);
        }

        $this->customers->update($id, $payload);
        AuditLog::record('customer.updated', 'customer', $id);
        Session::flash('success', 'Klant bijgewerkt.');
        $this->redirect('/customers/' . $id);
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('customers.delete', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        $this->customers->delete($id);
        AuditLog::record('customer.deleted', 'customer', $id);

        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        Session::flash('success', 'Klant verwijderd.');
        $this->redirect('/customers');
    }

    public function addNote(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $body = trim((string) $request->input('body', ''));
        if ($body === '') {
            $this->back();
        }
        (new Note())->create([
            'customer_id' => $id,
            'user_id'     => Auth::id(),
            'body'        => $body,
            'pinned'      => (int) (bool) $request->input('pinned'),
        ]);
        (new Activity())->log([
            'customer_id' => $id, 'user_id' => Auth::id(), 'type' => 'note',
            'subject' => 'Notitie toegevoegd', 'body' => mb_substr($body, 0, 190),
        ]);
        Session::flash('success', 'Notitie opgeslagen.');
        $this->redirect('/customers/' . $id);
    }

    public function addActivity(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        $type = (string) $request->input('type', 'note');
        (new Activity())->log([
            'customer_id' => $id,
            'user_id'     => Auth::id(),
            'type'        => in_array($type, ['call', 'email', 'meeting', 'note', 'task'], true) ? $type : 'note',
            'subject'     => trim((string) $request->input('subject', '')) ?: ucfirst($type),
            'body'        => trim((string) $request->input('body', '')),
            'outcome'     => $request->input('outcome'),
        ]);
        Session::flash('success', 'Activiteit gelogd.');
        $this->redirect('/customers/' . $id);
    }

    private function lookups(): array
    {
        $db = Database::instance();
        return [
            'sectors'  => $db->all('SELECT id, name FROM sectors ORDER BY name'),
            'projects' => $db->all("SELECT id, name FROM projects WHERE status='active' ORDER BY name"),
            'owners'   => $db->all("SELECT id, name FROM users WHERE status='active' ORDER BY name"),
        ];
    }

    /** @param array<string,mixed> $data @param array<int,string> $keys */
    private function nullifyEmpty(array $data, array $keys): array
    {
        foreach ($keys as $k) {
            if (isset($data[$k]) && $data[$k] === '') {
                $data[$k] = null;
            }
        }
        return $data;
    }
}
