<?php
/**
 * Audit trail writer. Records security-relevant and data-mutating events.
 */

declare(strict_types=1);

namespace App\Core;

final class AuditLog
{
    public static function record(string $action, string $entityType = '', ?int $entityId = null, array $meta = []): void
    {
        try {
            Database::instance()->insert('audit_logs', [
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'meta'        => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Audit log failed: ' . $e->getMessage());
        }
    }
}
