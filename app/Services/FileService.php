<?php
/**
 * Secure file upload handling with validation, safe storage and version
 * history. Files are stored outside the web root (storage/uploads) with random
 * names; downloads are streamed through a controller so access can be checked.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class FileService
{
    private const MAX_BYTES = 20 * 1024 * 1024; // 20 MB

    /** Allowed extension => MIME whitelist. */
    private const ALLOWED = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
        'zip'  => 'application/zip',
    ];

    /**
     * Validate and store an uploaded file, returning attachment metadata.
     *
     * @param array{name:string, tmp_name:string, size:int, error:int} $file
     * @return array{original_name:string, stored_name:string, mime_type:string, size_bytes:int}
     */
    public static function store(array $file, string $entityType, int $entityId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload mislukt.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('Bestand is te groot (max 20 MB).');
        }

        $original = (string) $file['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            throw new RuntimeException('Bestandstype niet toegestaan.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        // Loose check: the detected MIME should map to an allowed type.
        if (!in_array($mime, self::ALLOWED, true) && !str_starts_with($mime, 'image/') && !str_starts_with($mime, 'text/')) {
            throw new RuntimeException('Bestandsinhoud komt niet overeen met het type.');
        }

        $dir = (string) Config::get('paths.uploads') . '/' . $entityType . '/' . $entityId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Kan opslagmap niet aanmaken.');
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            // Fallback for CLI/testing where move_uploaded_file is unavailable.
            if (!@rename($file['tmp_name'], $target)) {
                throw new RuntimeException('Opslaan mislukt.');
            }
        }

        return [
            'original_name' => $original,
            'stored_name'   => $entityType . '/' . $entityId . '/' . $stored,
            'mime_type'     => self::ALLOWED[$ext],
            'size_bytes'    => (int) $file['size'],
        ];
    }

    public static function absolutePath(string $storedName): string
    {
        return (string) Config::get('paths.uploads') . '/' . $storedName;
    }

    public static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $val = (float) $bytes;
        while ($val >= 1024 && $i < count($units) - 1) {
            $val /= 1024;
            $i++;
        }
        return round($val, $i ? 1 : 0) . ' ' . $units[$i];
    }
}
