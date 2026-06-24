<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Container;

use Psr\Container\ContainerInterface;

/**
 * The Request-Scope Container.
 *
 * This container is created at the beginning of each HTTP request and destroyed
 * (unset + garbage collected) at the end of each request cycle. It holds
 * request-specific instances (e.g., the PSR-7 ServerRequest, the authenticated User,
 * session flash data).
 *
 * RESOLUTION STRATEGY:
 * 1. Check local `$instances` first (request-scoped bindings).
 * 2. If not found, delegate to the `$fallback` ApplicationContainer.
 *
 * MEMORY SAFETY:
 * - This container MUST be `unset()` at the end of each request.
 * - The ApplicationContainer is injected by reference, NOT copied.
 * - No data stored in this container persists across requests.
 */
class RequestContainer implements ContainerInterface
{
    /**
     * The fallback application-scope container.
     */
    private ApplicationContainer $fallback;

    /**
     * Request-scoped instances.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Create a new RequestContainer.
     *
     * @param  ApplicationContainer  $fallbackContainer  The application-scope container to delegate to.
     */
    public function __construct(ApplicationContainer $fallbackContainer)
    {
        $this->fallback = $fallbackContainer;
        $this->instances[ContainerInterface::class] = $this;
    }

    /**
     * Bind an instance into the request scope.
     *
     * @param  string  $abstract  The abstract type or interface name.
     * @param  mixed  $instance  The pre-built instance to bind.
     * @return void
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a service from the request scope, falling back to the application scope.
     *
     * @param  string  $id  The service identifier.
     * @return mixed
     *
     * @throws EntryNotFoundException  When the entry is not found in either scope.
     */
    public function get(string $id): mixed
    {
        // Check request-scoped instances first.
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!$this->fallback->has($id)) {
            throw new EntryNotFoundException(
                "No entry was found for identifier [{$id}] in the container."
            );
        }

        // Delegate to the application-scope container, passing $this as context.
        return $this->fallback->make($id, [], $this);
    }

    /**
     * Check if a service exists in the request scope or the application scope.
     *
     * @param  string  $id  The service identifier.
     * @return bool
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->instances)) {
            return true;
        }

        return $this->fallback->has($id);
    }
}
