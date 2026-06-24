<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * Circular dependency class A (depends on B).
 */
class CircularA
{
    public function __construct(
        public readonly CircularB $b,
    ) {
    }
}
