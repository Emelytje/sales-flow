<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Services\WebhookService;
use App\Models\Customer;

final class CustomerApiController extends Controller
{
    private Customer $customers;

    public function __construct()
    {
        $this->customers = new Customer();
    }

    public function index(Request $request): never
    {
        $result = $this->customers->listing(
            (int) $request->query('page', '1'),
            min(100, (int) $request->query('per_page', '25')),
            trim((string) $request->query('q', '')),
            ['pipeline_stage' => $request->query('stage'), 'status' => $request->query('status')]
        );
        $this->json($result);
    }

    public function show(Request $request, array $params): never
    {
        $customer = $this->customers->findDetailed((int) $params['id']);
        if ($customer === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }
        $customer['contacts'] = $this->customers->contacts((int) $params['id']);
        $this->json($customer);
    }

    public function store(Request $request): never
    {
        $this->validate($request, ['company_name' => 'required|max:190', 'email' => 'email'], ['company_name' => 'Bedrijfsnaam']);
        $data = $request->only([
            'company_name', 'vat_number', 'email', 'phone', 'website', 'address',
            'postal_code', 'city', 'country', 'sector_id', 'pipeline_stage',
            'status', 'priority', 'estimated_value',
        ]);
        $data['owner_id'] = Auth::id();
        $data['created_by'] = Auth::id();
        $id = $this->customers->create($data);
        WebhookService::dispatch('customer.created', ['id' => $id] + $data);
        $this->json($this->customers->find($id), 201);
    }

    public function update(Request $request, array $params): never
    {
        $id = (int) $params['id'];
        if ($this->customers->find($id) === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }
        $this->customers->update($id, $request->only([
            'company_name', 'vat_number', 'email', 'phone', 'website', 'address',
            'postal_code', 'city', 'country', 'sector_id', 'pipeline_stage',
            'status', 'priority', 'estimated_value', 'lead_score',
        ]));
        WebhookService::dispatch('customer.updated', ['id' => $id]);
        $this->json($this->customers->find($id));
    }

    public function destroy(Request $request, array $params): never
    {
        $id = (int) $params['id'];
        $this->customers->delete($id);
        WebhookService::dispatch('customer.deleted', ['id' => $id]);
        $this->json(['ok' => true]);
    }
}
