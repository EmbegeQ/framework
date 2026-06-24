<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Container;

use Closure;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * The Application-Scope Dependency Injection Container.
 *
 * This container is instantiated ONCE when the worker process starts and lives in
 * memory indefinitely. It manages singleton services, factory bindings, and provides
 * Reflection-based constructor autowiring.
 *
 * MEMORY SAFETY:
 * - All properties are instance-level (NO static state).
 * - The Reflection cache persists across requests (boot-scope optimization) and is safe
 *   because it only caches immutable ReflectionClass metadata.
 * - Request-specific data MUST NEVER be stored here. Use RequestContainer instead.
 */
class ApplicationContainer implements ContainerInterface
{
    /**
     * The container's bindings (factory closures keyed by abstract name).
     *
     * @var array<string, array{concrete: Closure, shared: bool}>
     */
    private array $bindings = [];

    /**
     * The container's shared instances (resolved singletons).
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * The registered aliases (alias -> abstract mapping).
     *
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Cached ReflectionClass instances for autowiring performance.
     *
     * This is safe to persist across requests because ReflectionClass
     * metadata is immutable and class-level (not request-level).
     *
     * @var array<string, ReflectionClass<object>>
     */
    private array $reflectionCache = [];

    /**
     * Stack of abstracts currently being resolved, for circular dependency detection.
     *
     * @var array<string, true>
     */
    private array $buildStack = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        $concrete = $this->normalizeConcrete($abstract, $concrete);

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => false,
        ];

        // If we already resolved this abstract as an instance, drop it
        // so the next resolution uses the new binding.
        unset($this->instances[$abstract]);
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $concrete = $this->normalizeConcrete($abstract, $concrete);

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $abstract, array $parameters = [], ?\Psr\Container\ContainerInterface $context = null): mixed
    {
        return $this->resolve($abstract, $parameters, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * {@inheritdoc}
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || isset($this->aliases[$abstract]);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        // Note: reflectionCache is intentionally NOT flushed.
        // It contains immutable class metadata that is safe to persist.
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract  The abstract type to resolve.
     * @param  array<string, mixed>  $parameters  Override parameters.
     * @return mixed
     *
     * @throws EntryNotFoundException
     * @throws BindingResolutionException
     */
    private function resolve(string $abstract, array $parameters = [], ?\Psr\Container\ContainerInterface $context = null): mixed
    {
        // Resolve aliases to their target abstract.
        $abstract = $this->getAlias($abstract);

        // Return a cached singleton instance if available and no override parameters.
        if (isset($this->instances[$abstract]) && $parameters === []) {
            return $this->instances[$abstract];
        }

        // If we have a binding, use its factory closure.
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $object = ($binding['concrete'])($context ?? $this, $parameters);

            // Cache if this is a shared (singleton) binding.
            if ($binding['shared'] && $parameters === []) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        // No binding found; attempt Reflection-based autowiring.
        return $this->buildFromReflection($abstract, $parameters, $context);
    }

    /**
     * Build a concrete instance using Reflection-based constructor autowiring.
     *
     * @param  string  $concrete  The fully-qualified class name to build.
     * @param  array<string, mixed>  $parameters  Override parameters.
     * @return object
     *
     * @throws BindingResolutionException
     */
    private function buildFromReflection(string $concrete, array $parameters = [], ?\Psr\Container\ContainerInterface $context = null): object
    {
        // Circular dependency detection.
        if (isset($this->buildStack[$concrete])) {
            throw new BindingResolutionException(
                "Circular dependency detected while resolving [{$concrete}]."
            );
        }

        $this->buildStack[$concrete] = true;

        try {
            $reflector = $this->getReflectionClass($concrete);

            if (!$reflector->isInstantiable()) {
                throw new BindingResolutionException(
                    "Target [{$concrete}] is not instantiable. Did you forget to bind an interface to a concrete class?"
                );
            }

            $constructor = $reflector->getConstructor();

            // No constructor means no dependencies; just instantiate.
            if ($constructor === null) {
                return $reflector->newInstance();
            }

            $dependencies = $this->resolveDependencies(
                $constructor->getParameters(),
                $parameters,
                $context,
            );

            return $reflector->newInstanceArgs($dependencies);
        } finally {
            unset($this->buildStack[$concrete]);
        }
    }

    /**
     * Resolve all constructor dependencies for a set of ReflectionParameters.
     *
     * @param  ReflectionParameter[]  $deps  The constructor parameters.
     * @param  array<string, mixed>  $overrides  User-provided parameter overrides.
     * @return array<int, mixed>  The resolved dependency values.
     *
     * @throws BindingResolutionException
     */
    private function resolveDependencies(array $deps, array $overrides = [], ?\Psr\Container\ContainerInterface $context = null): array
    {
        $resolved = [];

        foreach ($deps as $dep) {
            $name = $dep->getName();

            // Check for user-provided override by parameter name.
            if (array_key_exists($name, $overrides)) {
                $resolved[] = $overrides[$name];
                continue;
            }

            $type = $dep->getType();

            // If the parameter has a class/interface type hint, resolve it from the container.
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string $typeName */
                $typeName = $type->getName();

                try {
                    $resolved[] = $context ? $context->get($typeName) : $this->make($typeName);
                } catch (BindingResolutionException | \Psr\Container\NotFoundExceptionInterface $e) {
                    // If the dependency is optional (nullable), use null.
                    if ($dep->allowsNull()) {
                        $resolved[] = null;
                    } elseif ($dep->isDefaultValueAvailable()) {
                        $resolved[] = $dep->getDefaultValue();
                    } else {
                        throw new BindingResolutionException(
                            "Unresolvable dependency [{$name}] of type [{$typeName}] in class being built.",
                            previous: $e,
                        );
                    }
                }
                continue;
            }

            // For built-in types (string, int, etc.), check for a default value.
            if ($dep->isDefaultValueAvailable()) {
                $resolved[] = $dep->getDefaultValue();
                continue;
            }

            if ($dep->allowsNull()) {
                $resolved[] = null;
                continue;
            }

            throw new BindingResolutionException(
                "Unresolvable dependency [{$name}]: no type hint, no default value, and not nullable."
            );
        }

        return $resolved;
    }

    /**
     * Get a cached ReflectionClass instance for the given class name.
     *
     * @param  string  $className  The fully-qualified class name.
     * @return ReflectionClass<object>
     *
     * @throws BindingResolutionException
     */
    private function getReflectionClass(string $className): ReflectionClass
    {
        if (isset($this->reflectionCache[$className])) {
            return $this->reflectionCache[$className];
        }

        if (!class_exists($className) && !interface_exists($className)) {
            throw new BindingResolutionException(
                "Target class [{$className}] does not exist."
            );
        }

        /** @var class-string $className */
        $reflector = new ReflectionClass($className);

        $this->reflectionCache[$className] = $reflector;

        return $reflector;
    }

    /**
     * Resolve the alias chain to the final abstract type.
     *
     * @param  string  $abstract  The abstract or alias.
     * @return string  The resolved abstract.
     */
    private function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Normalize the concrete value into a Closure factory.
     *
     * @param  string  $abstract  The abstract type being registered.
     * @param  Closure|string|null  $concrete  The raw concrete value.
     * @return Closure  A closure that receives the container and returns the resolved object.
     */
    private function normalizeConcrete(string $abstract, Closure|string|null $concrete): Closure
    {
        // If no concrete is given, assume the abstract is a concrete class name (self-binding).
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // If the concrete is a class name string, wrap it in a closure that autowires it.
        if (is_string($concrete)) {
            return function (\Psr\Container\ContainerInterface $container, array $parameters = []) use ($concrete): mixed {
                return $this->buildFromReflection($concrete, $parameters, $container);
            };
        }

        return $concrete;
    }

    // =========================================================================
    // PSR-11: ContainerInterface
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            return $this->resolve($id);
        }

        throw new EntryNotFoundException(
            "No entry was found for identifier [{$id}] in the container."
        );
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return $this->bound($id) || $this->canBuildFromReflection($id);
    }

    /**
     * Determine if a class can be autowired via Reflection (i.e., it exists and is instantiable).
     *
     * @param  string  $className  The class name to check.
     * @return bool
     */
    private function canBuildFromReflection(string $className): bool
    {
        try {
            $reflector = $this->getReflectionClass($className);

            return $reflector->isInstantiable();
        } catch (BindingResolutionException) {
            return false;
        }
    }

    // =========================================================================
    // ArrayAccess Implementation (DX convenience)
    // =========================================================================

    /**
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    /**
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->make((string) $offset);
    }

    /**
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->instance((string) $offset, $value);
    }

    /**
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->bindings[(string) $offset], $this->instances[(string) $offset]);
    }
}
