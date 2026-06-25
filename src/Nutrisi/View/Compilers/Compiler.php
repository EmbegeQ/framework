<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers;

use EmbegeQ\Nutrisi\View\Filesystem;
use InvalidArgumentException;

/**
 * Base view compiler.
 */
abstract class Compiler
{
    public function __construct(
        protected Filesystem $files,
        protected string $cachePath,
        protected string $basePath = '',
        protected bool $shouldCache = true,
        protected bool $shouldCheckTimestamps = true,
    ) {
        if ($this->cachePath === '') {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }
    }

    public function getCompiledPath(string $path): string
    {
        $relative = $this->basePath !== '' && str_starts_with($path, $this->basePath)
            ? substr($path, strlen($this->basePath))
            : $path;

        return $this->cachePath . DIRECTORY_SEPARATOR . hash('xxh128', 'v1' . $relative) . '.php';
    }

    public function isExpired(string $path): bool
    {
        if (!$this->shouldCache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        if (!$this->files->exists($compiled)) {
            return true;
        }

        if (!$this->shouldCheckTimestamps) {
            return false;
        }

        return $this->files->lastModified($compiled) < $this->files->lastModified($path);
    }
}
