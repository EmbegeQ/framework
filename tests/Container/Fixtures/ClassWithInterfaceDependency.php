<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * A class that depends on an interface (for interface binding tests).
 */
class ClassWithInterfaceDependency
{
    public function __construct(
        public readonly DummyInterface $service,
    ) {
    }
}
