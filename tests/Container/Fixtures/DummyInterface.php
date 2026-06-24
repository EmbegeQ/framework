<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container\Fixtures;

/**
 * An interface for testing interface-to-concrete binding resolution.
 */
interface DummyInterface
{
    public function execute(): string;
}
