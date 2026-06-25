<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

/**
 * Simple local filesystem helper for view compilation.
 */
class Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function get(string $path): string
    {
        if (!$this->isFile($path)) {
            throw new \RuntimeException("File does not exist at path {$path}.");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read file at path {$path}.");
        }

        return $contents;
    }

    public function put(string $path, string $contents): bool
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException("Unable to create directory {$directory}.");
        }

        return file_put_contents($path, $contents) !== false;
    }

    public function lastModified(string $path): int
    {
        return (int) filemtime($path);
    }

    public function isFile(string $path): bool
    {
        return is_file($path);
    }
}
