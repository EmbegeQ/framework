<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * A class that depends on SimpleClass (for autowiring tests).
 */
class ClassWithDependency
{
    public function __construct(
        public readonly SimpleClass $dependency,
    ) {
    }
}
