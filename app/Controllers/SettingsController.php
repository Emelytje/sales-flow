<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

final class SettingsController extends Controller
{
    /** @var array<int,string> Whitelisted setting keys editable via the UI. */
    private const KEYS = [
        'company_name', 'company_email', 'company_phone', 'company_address',
        'company_vat', 'company_iban', 'currency', 'default_tax_rate',
        'quotation_prefix', 'quotation_valid_days', 'primary_color', 'accent_color',
        'general_number', 'sales_number', 'cti_webhook_secret',
    ];

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('settings.manage', $request);
        $this->view('settings/index', [
            'title'    => 'Instellingen',
            'settings' => $this->allSettings(),
            'appUrl'   => rtrim((string) config('app.url'), '/'),
        ]);
    }

    public function update(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('settings.manage', $request);
        $this->verifyCsrf($request);

        $db = Database::instance();
        foreach (self::KEYS as $key) {
            $value = $request->input($key);
            if ($value === null) {
                continue;
            }
            $existing = $db->first('SELECT setting_key FROM settings WHERE setting_key = ?', [$key]);
            if ($existing) {
                $db->update('settings', ['value' => (string) $value, 'updated_at' => date('Y-m-d H:i:s')], ['setting_key' => $key]);
            } else {
                $db->insert('settings', ['setting_key' => $key, 'value' => (string) $value, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        AuditLog::record('settings.updated');
        Session::flash('success', 'Instellingen opgeslagen.');
        $this->redirect('/settings');
    }

    public function audit(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('settings.manage', $request);
        $page = max(1, (int) $request->query('page', '1'));
        $perPage = 40;
        $offset = ($page - 1) * $perPage;
        $db = Database::instance();
        $total = (int) $db->scalar('SELECT COUNT(*) FROM audit_logs');
        $logs = $db->all(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset"
        );
        $this->view('settings/audit', [
            'title'  => 'Audit log',
            'logs'   => $logs,
            'result' => ['pages' => (int) ceil($total / $perPage), 'page' => $page, 'total' => $total],
        ]);
    }

    public function integrations(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('settings.manage', $request);
        $this->view('settings/integrations', [
            'title'    => 'Integraties',
            'google'   => (string) config('integrations.google.client_id') !== '',
            'maps'     => (string) config('integrations.google.maps_key') !== '',
            'ms'       => (string) config('integrations.microsoft.client_id') !== '',
            'push'     => getenv('VAPID_PUBLIC_KEY') !== false && getenv('VAPID_PUBLIC_KEY') !== '',
            'settings' => $this->allSettings(),
            'appUrl'   => rtrim((string) config('app.url'), '/'),
        ]);
    }

    /** @return array<string,string> */
    private function allSettings(): array
    {
        $rows = Database::instance()->all('SELECT setting_key, value FROM settings');
        $out = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = (string) $r['value'];
        }
        return $out;
    }
}
