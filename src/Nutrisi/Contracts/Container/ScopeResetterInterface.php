<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Container;

/**
 * Scope Resetter contract for the EmbegeQ framework.
 *
 * Application-scoped singletons that accumulate request-specific state
 * (e.g., query logs on a DatabaseManager, cached user data) MUST implement
 * this interface. The framework calls `reset()` on all registered resetters
 * at the end of each request cycle to prevent state bleeding between requests.
 *
 * This is CRITICAL for memory safety in stateful worker runtimes
 * (FrankenPHP, RoadRunner, Swoole).
 */
interface ScopeResetterInterface
{
    /**
     * Reset any request-specific state accumulated during the current request.
     *
     * After this method returns, the service MUST be in a clean state,
     * identical to its state immediately after boot.
     *
     * @return void
     */
    public function reset(): void;
}
