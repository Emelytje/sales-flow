<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Quotation extends Model
{
    protected string $table = 'quotations';
    protected array $fillable = [
        'number', 'customer_id', 'project_id', 'user_id', 'title', 'intro', 'terms',
        'currency', 'subtotal', 'discount', 'tax_rate', 'tax_amount', 'total',
        'status', 'valid_until', 'signed_at', 'signer_name', 'signature_data',
        'public_token', 'sent_at',
    ];

    public function items(int $quotationId): array
    {
        return $this->db()->all('SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC', [$quotationId]);
    }

    public function detailed(int $id): ?array
    {
        return $this->db()->first(
            "SELECT q.*, c.company_name, c.address, c.postal_code, c.city, c.vat_number, c.email AS customer_email,
                    u.name AS rep_name, u.email AS rep_email, u.phone AS rep_phone
             FROM quotations q
             JOIN customers c ON c.id = q.customer_id
             LEFT JOIN users u ON u.id = q.user_id
             WHERE q.id = ?",
            [$id]
        );
    }

    public function byToken(string $token): ?array
    {
        return $this->findBy(['public_token' => $token]);
    }

    /** Generate the next sequential quotation number using settings. */
    public function nextNumber(): string
    {
        $db = $this->db();
        $prefix = (string) ($db->scalar('SELECT value FROM settings WHERE setting_key = "quotation_prefix"') ?: 'OFF-');
        $next = (int) ($db->scalar('SELECT value FROM settings WHERE setting_key = "quotation_next"') ?: 1);
        $number = $prefix . date('Y') . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $db->run('UPDATE settings SET value = ? WHERE setting_key = "quotation_next"', [$next + 1]);
        return $number;
    }

    public function replaceItems(int $quotationId, array $items): array
    {
        $db = $this->db();
        $db->delete('quotation_items', ['quotation_id' => $quotationId]);
        $subtotal = 0.0;
        foreach ($items as $i => $item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $disc = (float) ($item['discount'] ?? 0);
            $line = $qty * $price * (1 - $disc / 100);
            $subtotal += $line;
            $db->insert('quotation_items', [
                'quotation_id' => $quotationId,
                'description'  => (string) ($item['description'] ?? ''),
                'quantity'     => $qty,
                'unit_price'   => $price,
                'discount'     => $disc,
                'line_total'   => round($line, 2),
                'sort_order'   => $i,
            ]);
        }
        return ['subtotal' => round($subtotal, 2)];
    }

    public function listing(int $page, int $perPage, string $search, ?string $status): array
    {
        $clauses = ['1'];
        $params = [];
        if ($status) {
            $clauses[] = 'q.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $clauses[] = '(q.number LIKE ? OR q.title LIKE ? OR c.company_name LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%");
        }
        $where = implode(' AND ', $clauses);
        $total = (int) $this->db()->scalar("SELECT COUNT(*) FROM quotations q JOIN customers c ON c.id=q.customer_id WHERE $where", $params);
        $offset = (max(1, $page) - 1) * $perPage;
        $rows = $this->db()->all(
            "SELECT q.*, c.company_name FROM quotations q JOIN customers c ON c.id=q.customer_id
             WHERE $where ORDER BY q.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => max(1, $page), 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }
}
