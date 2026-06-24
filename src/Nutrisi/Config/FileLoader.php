<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Config;

/**
 * Configuration File Loader.
 *
 * Scans a directory for PHP configuration files and returns a merged
 * associative array. Each file is expected to return an array.
 *
 * Example:
 *   config/database.php  ->  returns ['driver' => 'mysql', ...]
 *   config/cache.php     ->  returns ['default' => 'file', ...]
 *
 * Result: ['database' => [...], 'cache' => [...]]
 */
class FileLoader
{
    /**
     * Load all configuration files from the given path.
     *
     * @param  string  $configPath  The absolute path to the configuration directory.
     * @return array<string, mixed>  A merged array keyed by filename (without extension).
     *
     * @throws \InvalidArgumentException  When the path does not exist or is not a directory.
     */
    public function load(string $configPath): array
    {
        if (!is_dir($configPath)) {
            throw new \InvalidArgumentException(
                "Configuration path [{$configPath}] does not exist or is not a directory."
            );
        }

        $config = [];

        $files = glob($configPath . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            return $config;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');

            $values = require $file;

            if (is_array($values)) {
                /** @var array<string, mixed> $values */
                $config[$key] = $values;
            }
        }

        return $config;
    }
}
