<?php
/**
 * Creates an online meeting link. Prefers a genuinely free option so the app
 * needs no paid account:
 *   1. Jitsi Meet (free, no account, instant link) — default.
 *   2. Microsoft Teams — only when Microsoft is configured AND connected.
 */

declare(strict_types=1);

namespace App\Services;

final class MeetingService
{
    /** Public Jitsi instance — free, no registration. */
    private const JITSI_BASE = 'https://meet.jit.si/';

    /**
     * @return array{provider:string, url:string}
     */
    public static function create(int $userId, string $subject, string $startsAt, string $endsAt, bool $preferTeams = false): array
    {
        if ($preferTeams && TeamsService::configured()) {
            $link = TeamsService::createMeeting($userId, $subject, $startsAt, $endsAt);
            if ($link !== null) {
                return ['provider' => 'teams', 'url' => $link];
            }
        }
        return ['provider' => 'jitsi', 'url' => self::jitsiLink($subject)];
    }

    /** Deterministic, unguessable room name — free and instant. */
    public static function jitsiLink(string $subject): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '', ucwords($subject)) ?: 'Meeting';
        $room = 'SalesFlow' . substr($slug, 0, 24) . bin2hex(random_bytes(6));
        return self::JITSI_BASE . $room;
    }
}
