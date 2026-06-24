<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Routing;

/**
 * Value object representing a route.
 */
class Route
{
    /**
     * @param  array<int, string>  $methods  HTTP methods this route responds to.
     * @param  string  $uri  The URI pattern (e.g. '/users/{id}').
     * @param  array{0:class-string, 1:string}|\Closure|string  $action  The route handler.
     * @param  array<int, string|\Psr\Http\Server\MiddlewareInterface|callable>  $middleware  Route-specific middlewares.
     * @param  string|null  $name  The name of the route.
     */
    public function __construct(
        private array $methods,
        private string $uri,
        private mixed $action,
        private array $middleware = [],
        private ?string $name = null
    ) {}

    /**
     * Get the HTTP methods for the route.
     *
     * @return array<int, string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the URI pattern for the route.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route action.
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Get the route-specific middleware stack.
     *
     * @return array<int, string|\Psr\Http\Server\MiddlewareInterface|callable>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Add middleware to the route.
     *
     * @param  string|\Psr\Http\Server\MiddlewareInterface|callable|array<int, string|\Psr\Http\Server\MiddlewareInterface|callable>  $middleware
     * @return $this
     */
    public function middleware(mixed $middleware): self
    {
        if (is_array($middleware)) {
            /** @var array<int, string|\Psr\Http\Server\MiddlewareInterface|callable> $middleware */
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            /** @var string|\Psr\Http\Server\MiddlewareInterface|callable $middleware */
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Set the name of the route.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name of the route.
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
