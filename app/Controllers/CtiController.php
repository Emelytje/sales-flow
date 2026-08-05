<?php
/**
 * CTI endpoints: reverse lookup, inbound-call webhook, agent polling and a
 * ready-made screen-pop page for softphones (e.g. MicroSIP "open URL on call").
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\CtiService;

final class CtiController extends Controller
{
    /**
     * GET /api/v1/cti/lookup?number=+32...
     * Public reverse lookup for softphones. Protected by a shared secret
     * (query `key` or X-CTI-Secret header) when one is configured.
     */
    public function lookup(Request $request): never
    {
        $this->guardSecret($request);
        $number = (string) $request->query('number', '');
        if ($number === '') {
            Response::json(['found' => false, 'error' => 'number ontbreekt'], 400);
        }
        Response::json((new CtiService())->lookup($number));
    }

    /**
     * POST /api/v1/cti/incoming
     * Webhook fired by the PBX/VoIP provider on an inbound call.
     * Body/query: caller (or from), called (or to), agent (optional).
     */
    public function incoming(Request $request): never
    {
        $this->guardSecret($request);
        $caller = (string) ($request->input('caller') ?? $request->input('from') ?? '');
        $called = $request->input('called') ?? $request->input('to');
        $agent  = $request->input('agent');

        if ($caller === '') {
            Response::json(['ok' => false, 'error' => 'caller ontbreekt'], 400);
        }

        $result = (new CtiService())->handleIncoming(
            $caller,
            $called !== null ? (string) $called : null,
            $agent !== null ? (string) $agent : null
        );
        Response::json(['ok' => true] + $result);
    }

    /**
     * GET /api/v1/cti/poll  (session-authenticated, called by the browser widget)
     */
    public function poll(Request $request): never
    {
        if (!Auth::check()) {
            Response::json(['calls' => []], 401);
        }
        $calls = (new CtiService())->poll((int) Auth::id());
        Response::json(['calls' => array_map(static function (array $c): array {
            return [
                'id'          => (int) $c['id'],
                'number'      => $c['caller_number'],
                'name'        => $c['caller_name'],
                'customer_id' => $c['customer_id'] ? (int) $c['customer_id'] : null,
                'url'         => $c['customer_id'] ? '/customers/' . (int) $c['customer_id'] : null,
                'at'          => $c['created_at'],
            ];
        }, $calls)]);
    }

    /**
     * GET /cti/popup?number=...  — full-page screen pop for MicroSIP-style
     * "open URL on incoming call". Shows the resolved caller instantly.
     */
    public function popup(Request $request): never
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        $number = (string) $request->query('number', '');
        $match = $number !== '' ? (new CtiService())->lookup($number) : ['found' => false];
        $this->view('cti/popup', [
            'title'  => 'Inkomende oproep',
            'number' => CtiService::normalize($number),
            'match'  => $match,
        ], 'layouts/blank');
    }

    private function guardSecret(Request $request): void
    {
        $secret = CtiService::getSetting('cti_webhook_secret');
        if ($secret === null || $secret === '') {
            return; // No secret configured → open (recommend setting one).
        }
        $provided = $request->input('key') ?? $request->header('X-CTI-Secret');
        if (!is_string($provided) || !hash_equals($secret, $provided)) {
            Response::json(['error' => 'Ongeldige CTI-sleutel.'], 403);
        }
    }
}
