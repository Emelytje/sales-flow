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
use App\Models\Quotation;

final class QuotationController extends Controller
{
    private Quotation $quotations;

    public function __construct()
    {
        $this->quotations = new Quotation();
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.view', $request);
        $this->view('quotations/index', [
            'title'  => 'Offertes',
            'result' => $this->quotations->listing((int) $request->query('page', '1'), 20, trim((string) $request->query('q', '')), $request->query('status')),
            'search' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
        ]);
    }

    public function create(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.create', $request);
        $this->view('quotations/form', [
            'title'     => 'Nieuwe offerte',
            'quotation' => null,
            'items'     => [],
            'customers' => Database::instance()->all('SELECT id, company_name FROM customers ORDER BY company_name LIMIT 1000'),
            'taxRate'   => (float) (Database::instance()->scalar('SELECT value FROM settings WHERE setting_key="default_tax_rate"') ?: 21),
            'preselect' => (int) $request->query('customer_id', '0'),
        ]);
    }

    public function store(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.create', $request);
        $this->verifyCsrf($request);
        $id = $this->persist($request, null);
        Session::flash('success', 'Offerte aangemaakt.');
        $this->redirect('/quotations/' . $id);
    }

    public function show(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.view', $request);
        $id = (int) $params['id'];
        $q = $this->quotations->detailed($id);
        if ($q === null) {
            $this->redirect('/quotations');
        }
        $this->view('quotations/show', [
            'title'     => $q['number'],
            'quotation' => $q,
            'items'     => $this->quotations->items($id),
            'company'   => $this->companySettings(),
            'appUrl'    => rtrim((string) config('app.url'), '/'),
        ]);
    }

    public function edit(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.edit', $request);
        $id = (int) $params['id'];
        $q = $this->quotations->find($id);
        if ($q === null) {
            $this->redirect('/quotations');
        }
        $this->view('quotations/form', [
            'title'     => 'Offerte bewerken',
            'quotation' => $q,
            'items'     => $this->quotations->items($id),
            'customers' => Database::instance()->all('SELECT id, company_name FROM customers ORDER BY company_name LIMIT 1000'),
            'taxRate'   => (float) $q['tax_rate'],
            'preselect' => (int) $q['customer_id'],
        ]);
    }

    public function update(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.edit', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        if ($this->quotations->find($id) === null) {
            $this->redirect('/quotations');
        }
        $this->persist($request, $id);
        Session::flash('success', 'Offerte bijgewerkt.');
        $this->redirect('/quotations/' . $id);
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('quotations.delete', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $this->quotations->delete($id);
        AuditLog::record('quotation.deleted', 'quotation', $id);
        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        Session::flash('success', 'Offerte verwijderd.');
        $this->redirect('/quotations');
    }

    /** Printable A4 view (browser print-to-PDF — works everywhere, free). */
    public function pdf(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $id = (int) $params['id'];
        $q = $this->quotations->detailed($id);
        if ($q === null) {
            $this->redirect('/quotations');
        }
        $settings = $this->companySettings();
        $this->view('quotations/pdf', [
            'title'     => $q['number'],
            'quotation' => $q,
            'items'     => $this->quotations->items($id),
            'company'   => $settings,
        ], 'layouts/blank');
    }

    /** Public, tokenized quotation view for the customer. */
    public function publicShow(Request $request, array $params): never
    {
        $token = (string) ($params['token'] ?? '');
        $q = $this->quotations->byToken($token);
        if ($q === null) {
            http_response_code(404);
            echo \App\Core\View::render('errors/404', ['title' => 'Offerte niet gevonden'], 'layouts/blank');
            exit;
        }
        $full = $this->quotations->detailed((int) $q['id']);
        // Mark as viewed the first time.
        if ($q['status'] === 'sent') {
            $this->quotations->update((int) $q['id'], ['status' => 'viewed']);
        }
        $this->view('quotations/public', [
            'title'     => $full['number'],
            'quotation' => $full,
            'items'     => $this->quotations->items((int) $q['id']),
            'company'   => $this->companySettings(),
        ], 'layouts/blank');
    }

    /** Record the customer's digital signature and accept the quotation. */
    public function sign(Request $request, array $params): never
    {
        $token = (string) ($params['token'] ?? '');
        $q = $this->quotations->byToken($token);
        if ($q === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }
        $name = trim((string) $request->input('signer_name', ''));
        $signature = (string) $request->input('signature_data', '');
        if ($name === '' || !str_starts_with($signature, 'data:image')) {
            $this->json(['error' => 'Handtekening en naam zijn verplicht.'], 422);
        }
        $this->quotations->update((int) $q['id'], [
            'status'         => 'accepted',
            'signed_at'      => date('Y-m-d H:i:s'),
            'signer_name'    => $name,
            'signature_data' => $signature,
        ]);
        (new Activity())->log(['customer_id' => (int) $q['customer_id'], 'user_id' => $q['user_id'] ? (int) $q['user_id'] : null, 'type' => 'quotation', 'subject' => 'Offerte ' . $q['number'] . ' digitaal ondertekend door ' . $name]);
        AuditLog::record('quotation.signed', 'quotation', (int) $q['id'], ['signer' => $name]);
        if ($q['user_id']) {
            \App\Services\NotificationService::notify((int) $q['user_id'], 'quotation', 'Offerte ondertekend! 🎉', $q['number'] . ' is getekend door ' . $name, '/quotations/' . (int) $q['id'], 'check-circle');
        }
        $this->json(['ok' => true]);
    }

    /** Persist a quotation and its line items, recomputing totals. */
    private function persist(Request $request, ?int $id): int
    {
        $items = $request->input('items');
        $items = is_array($items) ? $items : [];
        $taxRate = (float) $request->input('tax_rate', 21);
        $discount = (float) $request->input('discount', 0);

        // Compute totals from items.
        $subtotal = 0.0;
        foreach ($items as $it) {
            $subtotal += (float) ($it['quantity'] ?? 1) * (float) ($it['unit_price'] ?? 0) * (1 - (float) ($it['discount'] ?? 0) / 100);
        }
        $subAfterDisc = max(0, $subtotal - $discount);
        $tax = round($subAfterDisc * $taxRate / 100, 2);
        $total = round($subAfterDisc + $tax, 2);

        $validDays = (int) (Database::instance()->scalar('SELECT value FROM settings WHERE setting_key="quotation_valid_days"') ?: 30);
        $data = [
            'customer_id' => (int) $request->input('customer_id'),
            'project_id'  => $request->input('project_id') ?: null,
            'user_id'     => Auth::id(),
            'title'       => (string) $request->input('title', 'Offerte'),
            'intro'       => $request->input('intro'),
            'terms'       => $request->input('terms'),
            'currency'    => 'EUR',
            'subtotal'    => round($subtotal, 2),
            'discount'    => $discount,
            'tax_rate'    => $taxRate,
            'tax_amount'  => $tax,
            'total'       => $total,
            'status'      => in_array($request->input('status'), ['draft', 'sent', 'accepted', 'rejected'], true) ? $request->input('status') : 'draft',
            'valid_until' => date('Y-m-d', strtotime("+{$validDays} days")),
        ];

        if ($id === null) {
            $data['number'] = $this->quotations->nextNumber();
            $data['public_token'] = bin2hex(random_bytes(20));
            $id = $this->quotations->create($data);
            (new Activity())->log(['customer_id' => $data['customer_id'], 'user_id' => Auth::id(), 'type' => 'quotation', 'subject' => 'Offerte ' . $data['number'] . ' aangemaakt']);
            AuditLog::record('quotation.created', 'quotation', $id);
        } else {
            $this->quotations->update($id, $data);
            AuditLog::record('quotation.updated', 'quotation', $id);
        }

        $this->quotations->replaceItems($id, $items);
        return $id;
    }

    /** @return array<string,string> */
    private function companySettings(): array
    {
        $rows = Database::instance()->all('SELECT setting_key, value FROM settings');
        $out = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = (string) $r['value'];
        }
        return $out;
    }
}
