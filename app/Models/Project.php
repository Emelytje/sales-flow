<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Project extends Model
{
    protected string $table = 'projects';
    protected array $fillable = [
        'name', 'description', 'logo', 'color', 'call_script', 'faq',
        'demo_video_url', 'status', 'created_by',
    ];
    protected array $searchable = ['name', 'description'];

    public function withStats(): array
    {
        return $this->db()->all(
            "SELECT p.*,
                (SELECT COUNT(*) FROM customers c WHERE c.project_id = p.id) AS customer_count,
                (SELECT COUNT(*) FROM quotations q WHERE q.project_id = p.id) AS quote_count
             FROM projects p ORDER BY p.status ASC, p.name ASC"
        );
    }
}
