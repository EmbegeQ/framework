<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * A class with a default parameter value.
 */
class ClassWithDefaults
{
    public function __construct(
        public readonly string $value = 'default',
    ) {
    }
}
