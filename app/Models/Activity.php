<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Activity extends Model
{
    protected string $table = 'activities';
    protected array $fillable = [
        'customer_id', 'contact_id', 'user_id', 'type', 'subject',
        'body', 'outcome', 'duration_seconds', 'occurred_at',
    ];

    /**
     * Log an activity and bump the customer's last_contact_at when relevant.
     */
    public function log(array $data): int
    {
        $data['occurred_at'] ??= date('Y-m-d H:i:s');
        $id = $this->create($data);

        if (!empty($data['customer_id']) && in_array($data['type'] ?? '', ['call', 'email', 'meeting'], true)) {
            $this->db()->update('customers', [
                'last_contact_at' => $data['occurred_at'],
                'updated_at'      => date('Y-m-d H:i:s'),
            ], ['id' => $data['customer_id']]);
        }
        return $id;
    }
}
