<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

use InvalidArgumentException;

/**
 * File-based view finder.
 */
class FileViewFinder implements ViewFinderInterface
{
    /**
     * @var array<int, string>
     */
    protected array $paths;

    /**
     * @var array<string, array<int, string>>
     */
    protected array $hints = [];

    /**
     * @param  array<int, string>  $paths
     */
    public function __construct(
        protected Filesystem $files,
        array $paths,
    ) {
        $this->paths = array_map(
            static fn (string $path): string => rtrim($path, '\/'),
            $paths
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $name): string
    {
        if ($this->files->exists($path = $this->findInPaths($name, $this->paths))) {
            return $path;
        }

        throw new InvalidArgumentException("View [{$name}] not found.");
    }

    /**
     * {@inheritdoc}
     */
    public function addLocation(string $location): void
    {
        $this->paths[] = rtrim($location, '\/');
    }

    /**
     * {@inheritdoc}
     */
    public function addNamespace(string $namespace, string|array $hints): void
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($this->hints[$namespace], $hints);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        try {
            $this->find($name);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function findInPaths(string $name, array $paths): string
    {
        [$namespace, $view] = $this->parseNamespaceSegments($name);

        if ($namespace !== null && isset($this->hints[$namespace])) {
            return $this->findInPaths($view, $this->hints[$namespace]);
        }

        foreach ($paths as $path) {
            $filePath = $path . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view);

            foreach (['.blade.php', '.php'] as $extension) {
                $candidate = $filePath . $extension;

                if ($this->files->isFile($candidate)) {
                    return $candidate;
                }
            }
        }

        return $path . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.blade.php';
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    protected function parseNamespaceSegments(string $name): array
    {
        $segments = explode('::', $name);

        if (count($segments) === 2) {
            return [$segments[0], $segments[1]];
        }

        return [null, $name];
    }
}
