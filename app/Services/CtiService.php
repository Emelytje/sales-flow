<?php
/**
 * Computer Telephony Integration (CTI) service.
 *
 * Handles phone-number normalization, reverse lookup of an incoming caller
 * against customers and contacts, and queuing of "screen pop" events for the
 * right agent(s). Uses a short-polling delivery model (cti_calls table) so it
 * works on shared hosting (InfinityFree) where long-lived SSE connections are
 * not permitted; a VPS can layer SSE on top of the same table later.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class CtiService
{
    /** Default country calling code used when a national number is received. */
    private const DEFAULT_CC = '32'; // Belgium

    private function db(): Database
    {
        return Database::instance();
    }

    /**
     * Normalize a phone number to a comparable E.164-ish digit string.
     * Handles "0032 3 123 45 67", "+32...", "03/123.45.67", "003212345678".
     */
    public static function normalize(string $raw, string $defaultCc = self::DEFAULT_CC): string
    {
        $digits = preg_replace('/[^\d+]/', '', $raw) ?? '';
        if ($digits === '') {
            return '';
        }
        // 00xx international prefix → +
        if (str_starts_with($digits, '00')) {
            $digits = '+' . substr($digits, 2);
        }
        if (str_starts_with($digits, '+')) {
            return $digits;
        }
        // National leading zero → replace with country code.
        if (str_starts_with($digits, '0')) {
            return '+' . $defaultCc . ltrim($digits, '0');
        }
        return '+' . $digits;
    }

    /**
     * Build candidate match keys so a stored "03 123 45 67" still matches an
     * inbound "+3231234567". We compare on the last significant digits.
     */
    private static function tail(string $normalized, int $len = 9): string
    {
        $digits = preg_replace('/\D/', '', $normalized) ?? '';
        return substr($digits, -$len);
    }

    /**
     * Reverse-lookup a caller number. Returns the best match with a link to
     * the customer record, or ['found' => false].
     *
     * @return array{found:bool, name?:string, company?:string, type?:string,
     *               customer_id?:int, contact_id?:int, url?:string, owner_id?:int|null}
     */
    public function lookup(string $number): array
    {
        $normalized = self::normalize($number);
        if ($normalized === '') {
            return ['found' => false];
        }
        $tail = self::tail($normalized);
        if (strlen($tail) < 6) {
            return ['found' => false];
        }
        $db = $this->db();

        // Strip every non-digit in SQL (MySQL 8 REGEXP_REPLACE) and compare on the
        // last 9 digits, so any stored format (0495…, +32…, 0032 …, 0495-12-34-56)
        // matches the inbound number regardless of punctuation.
        $contact = $db->first(
            "SELECT c.id AS contact_id, c.first_name, c.last_name, c.customer_id,
                    cu.company_name, cu.owner_id
             FROM contacts c JOIN customers cu ON cu.id = c.customer_id
             WHERE RIGHT(REGEXP_REPLACE(COALESCE(c.phone,''),  '[^0-9]', ''), 9) = ?
                OR RIGHT(REGEXP_REPLACE(COALESCE(c.mobile,''), '[^0-9]', ''), 9) = ?
             LIMIT 1",
            [$tail, $tail]
        );
        if ($contact !== null) {
            $name = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
            return [
                'found'       => true,
                'type'        => 'contact',
                'name'        => $name !== '' ? $name : $contact['company_name'],
                'company'     => $contact['company_name'],
                'customer_id' => (int) $contact['customer_id'],
                'contact_id'  => (int) $contact['contact_id'],
                'owner_id'    => $contact['owner_id'] !== null ? (int) $contact['owner_id'] : null,
                'url'         => '/customers/' . (int) $contact['customer_id'],
            ];
        }

        // 2) Company main number match.
        $customer = $db->first(
            "SELECT id, company_name, owner_id FROM customers
             WHERE RIGHT(REGEXP_REPLACE(COALESCE(phone,''), '[^0-9]', ''), 9) = ?
             LIMIT 1",
            [$tail]
        );
        if ($customer !== null) {
            return [
                'found'       => true,
                'type'        => 'customer',
                'name'        => $customer['company_name'],
                'company'     => $customer['company_name'],
                'customer_id' => (int) $customer['id'],
                'owner_id'    => $customer['owner_id'] !== null ? (int) $customer['owner_id'] : null,
                'url'         => '/customers/' . (int) $customer['id'],
            ];
        }

        return ['found' => false, 'number' => $normalized];
    }

    /**
     * Handle an inbound call: resolve the caller and queue a screen-pop for the
     * relevant agent(s). Returns the created lookup result.
     *
     * @param string      $caller    Calling party number.
     * @param string|null $called    The dialed number (to distinguish general vs sales line).
     * @param string|null $agentRef  Optional explicit target (user id or email) from the PBX.
     */
    public function handleIncoming(string $caller, ?string $called = null, ?string $agentRef = null): array
    {
        $match = $this->lookup($caller);
        $targets = $this->resolveTargets($called, $agentRef, $match);

        $now = date('Y-m-d H:i:s');
        $normalized = self::normalize($caller);
        foreach ($targets as $userId) {
            $this->db()->insert('cti_calls', [
                'user_id'       => $userId,
                'direction'     => 'inbound',
                'caller_number' => $normalized,
                'called_number' => $called ? self::normalize($called) : null,
                'caller_name'   => $match['found'] ? ($match['name'] ?? null) : null,
                'customer_id'   => $match['customer_id'] ?? null,
                'contact_id'    => $match['contact_id'] ?? null,
                'status'        => 'ringing',
                'created_at'    => $now,
            ]);
        }

        return $match + ['queued_for' => $targets];
    }

    /**
     * Decide which users should receive the pop.
     *
     * @return array<int, int> user ids
     */
    private function resolveTargets(?string $called, ?string $agentRef, array $match): array
    {
        $db = $this->db();

        // Explicit agent reference from the PBX wins.
        if ($agentRef !== null && $agentRef !== '') {
            $user = ctype_digit($agentRef)
                ? $db->first('SELECT id FROM users WHERE id = ? AND status="active"', [(int) $agentRef])
                : $db->first('SELECT id FROM users WHERE email = ? AND status="active"', [strtolower($agentRef)]);
            if ($user) {
                return [(int) $user['id']];
            }
        }

        // Route by the dialed line (sales number → sales team).
        $salesNumber = self::getSetting('sales_number');
        if ($called && $salesNumber && self::tail(self::normalize($called)) === self::tail(self::normalize($salesNumber))) {
            $rows = $db->all("SELECT id FROM users WHERE status='active' AND role IN('sales','manager','admin')");
            return array_map(static fn ($r) => (int) $r['id'], $rows);
        }

        // Prefer the record owner if known.
        if (!empty($match['owner_id'])) {
            return [(int) $match['owner_id']];
        }

        // Fallback: everyone active (general line).
        $rows = $db->all("SELECT id FROM users WHERE status='active'");
        return array_map(static fn ($r) => (int) $r['id'], $rows);
    }

    /** Pull undelivered pops for an agent and mark them delivered. */
    public function poll(int $userId): array
    {
        $db = $this->db();
        $rows = $db->all(
            "SELECT * FROM cti_calls WHERE user_id = ? AND status = 'ringing' AND delivered_at IS NULL
             ORDER BY created_at DESC LIMIT 5",
            [$userId]
        );
        if ($rows !== []) {
            $ids = implode(',', array_map(static fn ($r) => (int) $r['id'], $rows));
            $db->run("UPDATE cti_calls SET delivered_at = ? WHERE id IN ($ids)", [date('Y-m-d H:i:s')]);
        }
        return $rows;
    }

    public static function getSetting(string $key): ?string
    {
        $row = Database::instance()->first('SELECT value FROM settings WHERE setting_key = ?', [$key]);
        return $row['value'] ?? null;
    }
}
