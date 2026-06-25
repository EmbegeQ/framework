<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender;

interface QenderDirectiveRegistryInterface
{
    /**
     * Register a directive handler. Handler receives ($args, $renderer) and returns string.
     */
    public function register(string $name, callable $handler): void;
}
