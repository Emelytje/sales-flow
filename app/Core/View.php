<?php
/**
 * Simple, safe PHP template renderer with layout support.
 *
 * Templates are plain PHP files. The `e()` helper (defined in helpers.php)
 * must be used for any dynamic output to prevent XSS.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    private static string $layout = 'layouts/app';

    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::renderPartial($template, $data);

        $layout ??= self::$layout;
        if ($layout === null) {
            return $content;
        }

        $data['content'] = $content;
        return self::renderPartial($layout, $data);
    }

    /** @param array<string, mixed> $data */
    public static function renderPartial(string $template, array $data = []): string
    {
        $base = (string) Config::get('paths.views');
        $file = $base . '/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$template} ({$file})");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
