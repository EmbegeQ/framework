<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Container;

use ArrayAccess;
use Closure;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * The core Container contract for the EmbegeQ framework.
 *
 * Extends PSR-11 with binding registration, singleton management,
 * Reflection-based autowiring, and ArrayAccess for developer convenience.
 *
 * @extends ArrayAccess<string, mixed>
 */
interface ContainerInterface extends PsrContainerInterface, ArrayAccess
{
    /**
     * Register a binding with the container.
     *
     * @param  string  $abstract  The abstract type or interface name.
     * @param  Closure|string|null  $concrete  The concrete resolution (closure, class name, or null for self-binding).
     * @return void
     */
    public function bind(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a shared (singleton) binding in the container.
     *
     * The resolved instance will be cached and returned on subsequent calls.
     * This cache persists for the lifetime of the ApplicationContainer (the entire worker process).
     *
     * @param  string  $abstract  The abstract type or interface name.
     * @param  Closure|string|null  $concrete  The concrete resolution.
     * @return void
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract  The abstract type or interface name.
     * @param  mixed  $instance  The pre-built instance to bind.
     * @return mixed  The instance that was bound.
     */
    public function instance(string $abstract, mixed $instance): mixed;

    /**
     * Resolve the given type from the container.
     *
     * Uses Reflection-based constructor autowiring when no explicit binding exists
     * for a concrete class name.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract  The abstract type to resolve.
     * @param  array<string, mixed>  $parameters  Override parameters for constructor injection.
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws \EmbegeQ\Nutrisi\Container\BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract  The original abstract type.
     * @param  string  $alias  The alias name to register.
     * @return void
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract  The abstract type to check.
     * @return bool
     */
    public function bound(string $abstract): bool;

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void;
}
