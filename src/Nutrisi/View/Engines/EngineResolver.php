<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Engines;

use EmbegeQ\Nutrisi\Contracts\View\EngineInterface;
use InvalidArgumentException;

/**
 * Resolves view engines by extension name.
 */
class EngineResolver
{
    /** @var array<string, EngineInterface> */
    protected array $resolved = [];

    /** @var array<string, callable(): EngineInterface> */
    protected array $resolvers = [];

    public function register(string $engine, callable $resolver): void
    {
        $this->resolved = [];
        $this->resolvers[$engine] = $resolver;
    }

    public function resolve(string $engine): EngineInterface
    {
        if (isset($this->resolved[$engine])) {
            return $this->resolved[$engine];
        }

        if (!isset($this->resolvers[$engine])) {
            throw new InvalidArgumentException("Engine [{$engine}] not registered.");
        }

        return $this->resolved[$engine] = ($this->resolvers[$engine])();
    }
}
