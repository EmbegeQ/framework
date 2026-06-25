<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Foundation;

use EmbegeQ\Nutrisi\Contracts\Support\Htmlable;
use EmbegeQ\Nutrisi\View\HtmlString;

/**
 * Vite asset helper for Blade @vite directive.
 *
 * Supports development (public/hot) and production (public/build/manifest.json).
 */
class Vite implements Htmlable, \Stringable
{
    /** @var array<int, string> */
    protected array $entryPoints = [];

    protected string $buildDirectory = 'build';

    protected string $manifestFilename = 'manifest.json';

    protected ?string $hotFile = null;

    protected string $publicDirectory = 'public';

    protected string $basePath = '';

    /**
     * @param  array<int, string>|string  $entryPoints
     */
    public function __invoke(array|string $entryPoints = []): HtmlString
    {
        if (is_string($entryPoints)) {
            $entryPoints = [$entryPoints];
        }

        $this->entryPoints = $entryPoints;

        return new HtmlString($this->toHtml());
    }

    public function withEntryPoints(array $entryPoints): static
    {
        $this->entryPoints = $entryPoints;

        return $this;
    }

    public function useBuildDirectory(string $directory): static
    {
        $this->buildDirectory = $directory;

        return $this;
    }

    public function useHotFile(string $path): static
    {
        $this->hotFile = $path;

        return $this;
    }

    public function usePublicDirectory(string $directory): static
    {
        $this->publicDirectory = $directory;

        return $this;
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toHtml(): string
    {
        if ($this->isRunningHot()) {
            return $this->hotAssetTags();
        }

        return $this->buildAssetTags();
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    protected function isRunningHot(): bool
    {
        return is_file($this->hotFilePath());
    }

    protected function hotFilePath(): string
    {
        return $this->hotFile ?? $this->publicPath('hot');
    }

    protected function hotAssetTags(): string
    {
        $url = trim((string) file_get_contents($this->hotFilePath()));
        $tags = '';

        foreach ($this->entryPoints as $entryPoint) {
            if (str_ends_with($entryPoint, '.css')) {
                $tags .= sprintf('<link rel="stylesheet" href="%s/%s">', rtrim($url, '/'), $entryPoint);
            } else {
                $tags .= sprintf('<script type="module" src="%s/%s"></script>', rtrim($url, '/'), $entryPoint);
            }
        }

        return $tags;
    }

    protected function buildAssetTags(): string
    {
        $manifest = $this->manifest();
        $tags = '';

        foreach ($this->entryPoints as $entryPoint) {
            if (!isset($manifest[$entryPoint])) {
                continue;
            }

            $chunk = $manifest[$entryPoint];
            $file = $chunk['file'];

            if (str_ends_with($entryPoint, '.css') || str_ends_with($file, '.css')) {
                $tags .= sprintf(
                    '<link rel="stylesheet" href="/%s/%s">',
                    trim($this->buildDirectory, '/'),
                    ltrim($file, '/')
                );
            } else {
                $tags .= sprintf(
                    '<script type="module" src="/%s/%s"></script>',
                    trim($this->buildDirectory, '/'),
                    ltrim($file, '/')
                );
            }

            foreach ($chunk['css'] ?? [] as $css) {
                $tags .= sprintf(
                    '<link rel="stylesheet" href="/%s/%s">',
                    trim($this->buildDirectory, '/'),
                    ltrim($css, '/')
                );
            }
        }

        return $tags;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function manifest(): array
    {
        $path = $this->manifestPath();

        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        /** @var array<string, array<string, mixed>> */
        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }

    protected function manifestPath(): string
    {
        return $this->publicPath($this->buildDirectory . '/' . $this->manifestFilename);
    }

    protected function publicPath(string $path = ''): string
    {
        $base = $this->basePath !== ''
            ? $this->basePath . DIRECTORY_SEPARATOR . $this->publicDirectory
            : $this->publicDirectory;

        return $path !== '' ? $base . DIRECTORY_SEPARATOR . $path : $base;
    }
}
