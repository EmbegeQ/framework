<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Events;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Event dispatcher contract for EmbegeQ.
 */
interface DispatcherInterface extends EventDispatcherInterface
{
    /**
     * Register an event listener with the dispatcher.
     *
     * @param string $event
     * @param callable|string $listener
     */
    public function listen(string $event, callable|string $listener): void;

    /**
     * Determine if a given event has listeners.
     *
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool;
}
