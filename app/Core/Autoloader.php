<?php
/**
 * PSR-4 style autoloader.
 *
 * InfinityFree / shared hosting friendly: no Composer required. Maps top-level
 * namespaces to source directories and lazily includes class files on demand.
 */

declare(strict_types=1);

namespace App\Core;

final class Autoloader
{
    /** @var array<string, string> namespace prefix => base directory */
    private array $prefixes = [];

    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/\\') . '/';
        $this->prefixes[$prefix] = $baseDir;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    public function load(string $class): void
    {
        foreach ($this->prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }
        }
    }
}
