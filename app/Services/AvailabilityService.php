<?php
/**
 * Computes bookable time slots for a sales rep, honouring their weekly
 * availability rules, existing calendar events, pending bookings and travel
 * time buffers. Falls back to Mon–Fri 09:00–17:00 / 30-min slots when no rules
 * are configured.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AvailabilityService
{
    public function __construct(private int $userId)
    {
    }

    /**
     * @return array<string, array<int, string>> date => list of HH:MM start times
     */
    public function slots(int $days = 14, int $durationMin = 30): array
    {
        $db = Database::instance();
        $rules = $db->all('SELECT * FROM availability_rules WHERE user_id = ?', [$this->userId]);
        if ($rules === []) {
            // Default: weekdays 09:00–17:00.
            for ($w = 1; $w <= 5; $w++) {
                $rules[] = ['weekday' => $w, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'slot_minutes' => 30];
            }
        }
        $byWeekday = [];
        foreach ($rules as $r) {
            $byWeekday[(int) $r['weekday']][] = $r;
        }

        // Busy windows from events + pending/approved bookings for the horizon.
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59', strtotime("+{$days} days"));
        $busy = $db->all(
            "SELECT starts_at, ends_at, travel_minutes FROM agenda_events WHERE user_id = ? AND status <> 'cancelled' AND ends_at > ? AND starts_at < ?",
            [$this->userId, $start, $end]
        );
        foreach ($db->all("SELECT starts_at, ends_at FROM bookings WHERE user_id = ? AND status IN('pending','approved') AND ends_at > ? AND starts_at < ?", [$this->userId, $start, $end]) as $b) {
            $busy[] = ['starts_at' => $b['starts_at'], 'ends_at' => $b['ends_at'], 'travel_minutes' => 0];
        }

        $result = [];
        $now = time();
        for ($d = 0; $d < $days; $d++) {
            $date = date('Y-m-d', strtotime("+{$d} days"));
            $weekday = (int) date('w', strtotime($date)); // 0=Sun..6=Sat
            if (empty($byWeekday[$weekday])) {
                continue;
            }
            $slots = [];
            foreach ($byWeekday[$weekday] as $rule) {
                $step = max(15, (int) $rule['slot_minutes']);
                $t = strtotime($date . ' ' . $rule['start_time']);
                $endT = strtotime($date . ' ' . $rule['end_time']);
                while ($t + $durationMin * 60 <= $endT) {
                    $slotStart = $t;
                    $slotEnd = $t + $durationMin * 60;
                    if ($slotStart > $now + 3600 && !$this->collides($slotStart, $slotEnd, $busy)) {
                        $slots[] = date('H:i', $slotStart);
                    }
                    $t += $step * 60;
                }
            }
            if ($slots !== []) {
                $result[$date] = array_values(array_unique($slots));
            }
        }
        return $result;
    }

    /** @param array<int,array<string,mixed>> $busy */
    private function collides(int $start, int $end, array $busy): bool
    {
        foreach ($busy as $b) {
            $bStart = strtotime($b['starts_at']) - ((int) ($b['travel_minutes'] ?? 0)) * 60;
            $bEnd = strtotime($b['ends_at']) + ((int) ($b['travel_minutes'] ?? 0)) * 60;
            if ($start < $bEnd && $end > $bStart) {
                return true;
            }
        }
        return false;
    }

    public function isFree(string $startsAt, string $endsAt): bool
    {
        return !$this->collides(strtotime($startsAt), strtotime($endsAt), Database::instance()->all(
            "SELECT starts_at, ends_at, travel_minutes FROM agenda_events WHERE user_id = ? AND status <> 'cancelled'
             UNION ALL SELECT starts_at, ends_at, 0 FROM bookings WHERE user_id = ? AND status IN('pending','approved')",
            [$this->userId, $this->userId]
        ));
    }
}
