<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * Circular dependency class B (depends on A).
 */
class CircularB
{
    public function __construct(
        public readonly CircularA $a,
    ) {
    }
}
