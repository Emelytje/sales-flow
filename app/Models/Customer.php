<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Customer extends Model
{
    protected string $table = 'customers';
    protected array $fillable = [
        'company_name', 'vat_number', 'kbo_number', 'email', 'phone', 'website',
        'linkedin', 'logo', 'address', 'postal_code', 'city', 'country',
        'latitude', 'longitude', 'google_place_id', 'sector_id', 'project_id',
        'owner_id', 'pipeline_stage', 'status', 'priority', 'lead_score',
        'estimated_value', 'lost_reason', 'next_action_at', 'last_contact_at',
        'notes', 'created_by',
    ];
    protected array $searchable = ['company_name', 'email', 'phone', 'city', 'vat_number'];

    /** Detailed record joined with related lookup data. */
    public function findDetailed(int $id): ?array
    {
        return $this->db()->first(
            "SELECT c.*, s.name AS sector_name, p.name AS project_name, p.color AS project_color,
                    u.name AS owner_name, u.color AS owner_color
             FROM customers c
             LEFT JOIN sectors s ON s.id = c.sector_id
             LEFT JOIN projects p ON p.id = c.project_id
             LEFT JOIN users u ON u.id = c.owner_id
             WHERE c.id = ?",
            [$id]
        );
    }

    /** Detect likely duplicates by name / VAT / email before creating. */
    public function findDuplicates(string $company, ?string $vat, ?string $email): array
    {
        $conds = ['company_name LIKE ?'];
        $params = ['%' . trim($company) . '%'];
        if ($vat) {
            $conds[] = 'vat_number = ?';
            $params[] = $vat;
        }
        if ($email) {
            $conds[] = 'email = ?';
            $params[] = $email;
        }
        return $this->db()->all(
            'SELECT id, company_name, city, vat_number FROM customers WHERE ' . implode(' OR ', $conds) . ' LIMIT 5',
            $params
        );
    }

    /** List with search + stage/owner filters, joined for display. */
    public function listing(int $page, int $perPage, string $search, array $filters): array
    {
        $clauses = ['1'];
        $params = [];
        foreach (['pipeline_stage' => 'c.pipeline_stage', 'status' => 'c.status', 'owner_id' => 'c.owner_id', 'sector_id' => 'c.sector_id', 'project_id' => 'c.project_id'] as $key => $col) {
            if (!empty($filters[$key])) {
                $clauses[] = "$col = ?";
                $params[] = $filters[$key];
            }
        }
        if ($search !== '') {
            $clauses[] = '(c.company_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.city LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }
        $where = implode(' AND ', $clauses);
        $total = (int) $this->db()->scalar("SELECT COUNT(*) FROM customers c WHERE $where", $params);

        $offset = (max(1, $page) - 1) * $perPage;
        $rows = $this->db()->all(
            "SELECT c.*, u.name AS owner_name, u.color AS owner_color, s.name AS sector_name
             FROM customers c
             LEFT JOIN users u ON u.id = c.owner_id
             LEFT JOIN sectors s ON s.id = c.sector_id
             WHERE $where ORDER BY c.updated_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => max(1, $page), 'per_page' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }

    public function contacts(int $customerId): array
    {
        return $this->db()->all('SELECT * FROM contacts WHERE customer_id = ? ORDER BY is_primary DESC, first_name ASC', [$customerId]);
    }

    public function timeline(int $customerId, int $limit = 40): array
    {
        return $this->db()->all(
            "SELECT a.*, u.name AS user_name, u.color AS user_color
             FROM activities a LEFT JOIN users u ON u.id = a.user_id
             WHERE a.customer_id = ? ORDER BY a.occurred_at DESC LIMIT $limit",
            [$customerId]
        );
    }

    public function notes(int $customerId): array
    {
        return $this->db()->all(
            "SELECT n.*, u.name AS user_name, u.color AS user_color
             FROM notes n LEFT JOIN users u ON u.id = n.user_id
             WHERE n.customer_id = ? ORDER BY n.pinned DESC, n.created_at DESC",
            [$customerId]
        );
    }
}
