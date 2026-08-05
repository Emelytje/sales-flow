<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\FileService;
use App\Core\Request;

final class AttachmentController extends Controller
{
    private const ENTITIES = ['customer', 'contact', 'project', 'quotation'];

    /** Upload one or more files for an entity. */
    public function store(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);

        $entityType = (string) $request->input('entity_type', 'customer');
        $entityId = (int) ($params['id'] ?? $request->input('entity_id'));
        if (!in_array($entityType, self::ENTITIES, true) || $entityId <= 0) {
            $this->json(['error' => 'Ongeldige entiteit.'], 422);
        }

        $file = $request->file('file');
        if ($file === null) {
            $this->json(['error' => 'Geen bestand ontvangen.'], 422);
        }

        try {
            $meta = FileService::store($file, $entityType, $entityId);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 422);
        }

        $db = Database::instance();
        // Version = previous versions of a same-named file + 1.
        $version = (int) $db->scalar(
            'SELECT COUNT(*) FROM attachments WHERE entity_type = ? AND entity_id = ? AND original_name = ?',
            [$entityType, $entityId, $meta['original_name']]
        ) + 1;

        $id = $db->insert('attachments', [
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'folder'        => $request->input('folder'),
            'original_name' => $meta['original_name'],
            'stored_name'   => $meta['stored_name'],
            'mime_type'     => $meta['mime_type'],
            'size_bytes'    => $meta['size_bytes'],
            'version'       => $version,
            'uploaded_by'   => Auth::id(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        AuditLog::record('attachment.uploaded', $entityType, $entityId, ['file' => $meta['original_name']]);

        $this->json(['ok' => true, 'id' => $id]);
    }

    public function download(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $att = Database::instance()->first('SELECT * FROM attachments WHERE id = ?', [(int) $params['id']]);
        if ($att === null) {
            http_response_code(404);
            exit;
        }
        $path = FileService::absolutePath($att['stored_name']);
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . $att['mime_type']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($att['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->verifyCsrf($request);
        $db = Database::instance();
        $att = $db->first('SELECT * FROM attachments WHERE id = ?', [(int) $params['id']]);
        if ($att !== null) {
            @unlink(FileService::absolutePath($att['stored_name']));
            $db->delete('attachments', ['id' => (int) $att['id']]);
            AuditLog::record('attachment.deleted', $att['entity_type'], (int) $att['entity_id']);
        }
        $this->json(['ok' => true]);
    }
}
