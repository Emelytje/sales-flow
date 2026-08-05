<?php
/**
 * Creates Microsoft Teams online meetings via Graph. Returns the join URL or
 * null when Microsoft isn't configured / connected for the user.
 */

declare(strict_types=1);

namespace App\Services;

final class TeamsService
{
    public static function configured(): bool
    {
        return MicrosoftGraph::configured();
    }

    public static function createMeeting(int $userId, string $subject, string $startsAt, string $endsAt): ?string
    {
        $result = MicrosoftGraph::post($userId, '/me/onlineMeetings', [
            'subject'      => $subject,
            'startDateTime' => date('c', strtotime($startsAt)),
            'endDateTime'   => date('c', strtotime($endsAt)),
        ]);
        return $result['joinWebUrl'] ?? null;
    }
}
