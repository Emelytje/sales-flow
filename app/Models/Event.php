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

    /**
     * Events visible to a user in a date range. Always includes the user's own
     * events (incl. private); shared events from colleagues are included and can
     * be narrowed to a specific set of owners via $ownerFilter. Private events
     * of other users are never returned.
     *
     * @param array<int,int> $ownerFilter when non-empty, limit colleagues shown
     */
    public function inRange(int $userId, string $start, string $end, array $ownerFilter = []): array
    {
        $params = [$end, $start, $userId, $userId];
        $filterSql = '';
        if ($ownerFilter !== []) {
            $ids = array_values(array_unique(array_map('intval', $ownerFilter)));
            $ids[] = $userId; // never hide your own events
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $filterSql = " AND e.user_id IN ($placeholders)";
            $params = array_merge($params, $ids);
        }

        return $this->db()->all(
            "SELECT e.*, c.company_name, u.name AS owner_name, u.color AS owner_color
             FROM agenda_events e
             LEFT JOIN customers c ON c.id = e.customer_id
             LEFT JOIN users u ON u.id = e.user_id
             WHERE e.starts_at < ? AND e.ends_at > ?
               AND (e.user_id = ? OR e.visibility = 'shared')
               AND (e.visibility = 'shared' OR e.user_id = ?)
               {$filterSql}
             ORDER BY e.starts_at ASC",
            $params
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
