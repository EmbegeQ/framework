<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

use EmbegeQ\Nutrisi\Contracts\View\FactoryInterface;
use EmbegeQ\Nutrisi\Contracts\View\ViewInterface;
use EmbegeQ\Nutrisi\View\Concerns\ManagesLayouts;
use EmbegeQ\Nutrisi\View\Engines\EngineResolver;

/**
 * View factory — the runtime environment for Blade templates.
 */
class Factory implements FactoryInterface
{
    use ManagesLayouts;

    /** @var array<string, mixed> */
    protected array $shared = [];

    /** @var array<string, string> */
    protected array $extensions = [
        'blade.php' => 'blade',
        'php' => 'php',
    ];

    public function __construct(
        protected EngineResolver $engines,
        protected ViewFinderInterface $finder,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function make(string $view, array $data = []): ViewInterface
    {
        $path = $this->finder->find($view);
        $engine = $this->engines->resolve($this->getEngineFromPath($path));

        return new View($this, $engine, $view, $path, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    /**
     * {@inheritdoc}
     */
    public function share(array|string $key, mixed $value = null): mixed
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);

            return $value;
        }

        return $this->shared[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getShared(): array
    {
        return $this->shared;
    }

    public function file(string $path, array $data = []): ViewInterface
    {
        $engine = $this->engines->resolve($this->getEngineFromPath($path));

        return new View($this, $engine, $path, $path, $data);
    }

    protected function getEngineFromPath(string $path): string
    {
        if (str_ends_with($path, '.blade.php')) {
            return 'blade';
        }

        return 'php';
    }
}
