<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Routing;

/**
 * A simple route collection to manage named routes.
 */
class RouteCollection
{
    /**
     * @var array<string, Route>
     */
    private array $namedRoutes = [];

    /**
     * Register a named route.
     */
    public function addNamedRoute(Route $route): void
    {
        if ($route->getName() === null) {
            return;
        }

        $this->namedRoutes[$route->getName()] = $route;
    }

    /**
     * Get a named route or null.
     */
    public function getNamedRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Generate a URI for a named route.
     *
     * @param array<string, mixed> $parameters
     */
    public function generateUri(string $name, array $parameters = []): string
    {
        $route = $this->getNamedRoute($name);

        if ($route === null) {
            throw new \InvalidArgumentException("Route [{$name}] not found.");
        }

        return $this->fillUriParameters($route->getUri(), $parameters);
    }

    /**
     * Replace route parameter placeholders with actual values.
     *
     * @param string $uri
     * @param array<string, mixed> $parameters
     */
    private function fillUriParameters(string $uri, array $parameters): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function (array $matches) use ($parameters) {
            $key = $matches[1];
            if (!array_key_exists($key, $parameters)) {
                throw new \InvalidArgumentException("Missing route parameter [{$key}].");
            }
            return rawurlencode((string) $parameters[$key]);
        }, $uri) ?: $uri;
    }
}
