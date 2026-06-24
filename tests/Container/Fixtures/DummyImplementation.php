<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * A concrete implementation of DummyInterface.
 */
class DummyImplementation implements DummyInterface
{
    public function execute(): string
    {
        return 'executed';
    }
}
