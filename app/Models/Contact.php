<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Contact extends Model
{
    protected string $table = 'contacts';
    protected array $fillable = [
        'customer_id', 'first_name', 'last_name', 'job_title',
        'email', 'phone', 'mobile', 'linkedin', 'is_primary',
    ];
    protected array $searchable = ['first_name', 'last_name', 'email', 'phone'];

    /** Clear the primary flag for all contacts of a customer. */
    public function unsetPrimary(int $customerId): void
    {
        $this->db()->run('UPDATE contacts SET is_primary = 0 WHERE customer_id = ?', [$customerId]);
    }

    public function forCustomer(int $customerId): array
    {
        return $this->db()->all(
            'SELECT * FROM contacts WHERE customer_id = ? ORDER BY is_primary DESC, first_name ASC',
            [$customerId]
        );
    }

    /** Listing joined with company name for the global contacts page. */
    public function listing(int $page, int $perPage, string $search): array
    {
        $params = [];
        $where = '1';
        if ($search !== '') {
            $where = '(ct.first_name LIKE ? OR ct.last_name LIKE ? OR ct.email LIKE ? OR cu.company_name LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }
        $total = (int) $this->db()->scalar(
            "SELECT COUNT(*) FROM contacts ct JOIN customers cu ON cu.id = ct.customer_id WHERE $where",
            $params
        );
        $offset = (max(1, $page) - 1) * $perPage;
        $rows = $this->db()->all(
            "SELECT ct.*, cu.company_name FROM contacts ct
             JOIN customers cu ON cu.id = ct.customer_id
             WHERE $where ORDER BY ct.first_name ASC LIMIT $perPage OFFSET $offset",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => max(1, $page), 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }
}
