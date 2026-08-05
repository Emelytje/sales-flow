<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Event extends Model
{
    protected string $table = 'agenda_events';
    protected array $fillable = [
        'user_id', 'customer_id', 'title', 'description', 'type', 'location',
        'starts_at', 'ends_at', 'all_day', 'travel_minutes', 'color', 'visibility',
        'status', 'recurrence_rule', 'google_event_id', 'teams_join_url', 'reminder_minutes',
    ];

    /** Events visible to a user in a date range (own + shared team events). */
    public function inRange(int $userId, string $start, string $end): array
    {
        return $this->db()->all(
            "SELECT e.*, c.company_name, u.name AS owner_name, u.color AS owner_color
             FROM agenda_events e
             LEFT JOIN customers c ON c.id = e.customer_id
             LEFT JOIN users u ON u.id = e.user_id
             WHERE e.starts_at < ? AND e.ends_at > ?
               AND (e.user_id = ? OR e.visibility = 'shared')
             ORDER BY e.starts_at ASC",
            [$end, $start, $userId]
        );
    }

    /** Detect a scheduling conflict for a user in a time window. */
    public function hasConflict(int $userId, string $start, string $end, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM agenda_events
                WHERE user_id = ? AND status <> 'cancelled'
                  AND starts_at < ? AND ends_at > ?";
        $params = [$userId, $end, $start];
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        return (int) $this->db()->scalar($sql, $params) > 0;
    }
}
