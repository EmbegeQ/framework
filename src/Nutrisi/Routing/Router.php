<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Routing;

use EmbegeQ\Nutrisi\Contracts\Routing\RouterInterface;
use EmbegeQ\Nutrisi\Http\MiddlewareDispatcher;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Nyholm\Psr7\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use EmbegeQ\Nutrisi\Routing\RouteCollection;

/**
 * FastRoute-backed Router.
 *
 * Manages route collection, groups, parameter matching, and reflection-based
 * action invocation with dependency resolution from the active container.
 */
class Router implements RouterInterface
{
    /**
     * The registered route collection.
     *
     * @var Route[]
     */
    private array $routes = [];

    /**
     * Named route collection.
     */
    private RouteCollection $routeCollection;

    /**
     * The compiled FastRoute dispatcher.
     */
    private ?Dispatcher $dispatcher = null;

    /**
     * The FastRoute collector.
     */
    private RouteCollector $collector;

    /**
     * The nested group attributes stack.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $groupStack = [];

    /**
     * Create a new Router instance.
     */
    public function __construct()
    {
        $this->collector = new RouteCollector(
            new RouteParser(),
            new DataGenerator()
        );
        $this->routeCollection = new RouteCollection();
    }

    /**
     * Get all registered routes.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get a named route URI by name.
     *
     * @param  string  $name
     * @param  array<string, mixed>  $parameters
     */
    public function route(string $name, array $parameters = []): string
    {
        return $this->routeCollection->generateUri($name, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $uri, array|string|\Closure $action): void
    {
        $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $uri, array|string|\Closure $action): void
    {
        $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $uri, array|string|\Closure $action): void
    {
        $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $uri, array|string|\Closure $action): void
    {
        $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Register a route directly.
     *
     * @param  array<int, string>  $methods
     * @param  string  $uri
     * @param  array{0:class-string, 1:string}|string|\Closure  $action
     * @return Route
     */
    public function addRoute(array $methods, string $uri, array|string|\Closure $action): Route
    {
        $uri = $this->prefixUri($uri);
        $middleware = $this->mergeGroupMiddleware();

        $route = new Route($methods, $uri, $action, $middleware);

        foreach ($methods as $method) {
            $this->collector->addRoute($method, $uri, $route);
        }

        $this->routes[] = $route;
        $this->routeCollection->addNamedRoute($route);
        $this->dispatcher = null; // Invalidate compiled dispatcher

        return $route;
    }

    /**
     * Prefix the URI with the current route group stack prefix.
     */
    private function prefixUri(string $uri): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix = rtrim($prefix, '/') . '/' . ltrim((string) $group['prefix'], '/');
            }
        }

        if ($prefix !== '') {
            $uri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
        }

        return '/' . ltrim($uri, '/');
    }

    /**
     * Merge route group middleware.
     *
     * @return array<int, string|\Psr\Http\Server\MiddlewareInterface|callable>
     */
    private function mergeGroupMiddleware(): array
    {
        $middlewares = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $groupMiddleware = (array) $group['middleware'];
                $middlewares = array_merge($middlewares, $groupMiddleware);
            }
        }
        return $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $this->dispatcher ??= new GroupCountBasedDispatcher($this->collector->getData());

        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => new Response(404, [], 'Not Found'),
            Dispatcher::METHOD_NOT_ALLOWED => new Response(405, [], 'Method Not Allowed'),
            Dispatcher::FOUND => $this->handleFoundRoute($request, $routeInfo[1], $routeInfo[2]),
            default => throw new \RuntimeException('Invalid routing state.'),
        };
    }

    /**
     * Process a matched route through its middleware chain and execute its action.
     *
     * @param  ServerRequestInterface  $request
     * @param  Route  $route
     * @param  array<string, mixed>  $params
     */
    private function handleFoundRoute(ServerRequestInterface $request, Route $route, array $params): ResponseInterface
    {
        // Hydrate the request with matched route parameters.
        foreach ($params as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $request = $request->withAttribute(Route::class, $route);

        $container = $request->getAttribute(ContainerInterface::class);
        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('RequestContainer missing from request attributes.');
        }

        $fallback = new class($this, $route) implements RequestHandlerInterface {
            public function __construct(private Router $router, private Route $route) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->router->runRoute($request, $this->route);
            }
        };

        $dispatcher = new MiddlewareDispatcher(
            $container,
            $route->getMiddleware(),
            $fallback
        );

        return $dispatcher->handle($request);
    }

    /**
     * Run the specific route action with automatic parameter injection.
     */
    public function runRoute(ServerRequestInterface $request, Route $route): ResponseInterface
    {
        $container = $request->getAttribute(ContainerInterface::class);
        if (!$container instanceof ContainerInterface) {
            throw new \RuntimeException('RequestContainer missing from request attributes.');
        }

        $action = $route->getAction();

        if ($action instanceof \Closure) {
            $reflector = new \ReflectionFunction($action);
            $dependencies = $this->resolveParameters($reflector, $request->getAttributes(), $container, $request);
            $response = $action(...$dependencies);
        } elseif (is_array($action)) {
            [$controllerClass, $method] = $action;
            $controller = $container->get($controllerClass);
            $reflector = new \ReflectionMethod($controller, $method);
            $dependencies = $this->resolveParameters($reflector, $request->getAttributes(), $container, $request);
            $response = $controller->{$method}(...$dependencies);
        } elseif (is_string($action)) {
            // Support String Controller syntax 'UserController@show'
            if (str_contains($action, '@')) {
                [$controllerClass, $method] = explode('@', $action, 2);
                $controller = $container->get($controllerClass);
                $reflector = new \ReflectionMethod($controller, $method);
                $dependencies = $this->resolveParameters($reflector, $request->getAttributes(), $container, $request);
                $response = $controller->{$method}(...$dependencies);
            } else {
                throw new \RuntimeException('Unsupported route action string format.');
            }
        } else {
            throw new \RuntimeException('Route action must be a Closure, string, or controller array.');
        }

        return $this->formatResponse($response);
    }

    /**
     * Resolve action method parameters using matched route attributes and container autowiring.
     *
     * @param  \ReflectionFunctionAbstract  $reflector
     * @param  array<string, mixed>  $routeParams
     * @param  ContainerInterface  $container
     * @param  ServerRequestInterface  $request
     * @return array<int, mixed>
     */
    private function resolveParameters(
        \ReflectionFunctionAbstract $reflector,
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        $resolved = [];

        foreach ($reflector->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            // 1. Inject the PSR-7 request if type-hinted.
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && is_a($type->getName(), ServerRequestInterface::class, true)) {
                $resolved[] = $request;
                continue;
            }

            // 2. Inject matched route parameter if matching name.
            if (array_key_exists($name, $routeParams)) {
                $resolved[] = $routeParams[$name];
                continue;
            }

            // 3. Inject container-resolved dependency.
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $resolved[] = $container->get($type->getName());
                continue;
            }

            // 4. Default / optional handling.
            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $resolved[] = null;
                continue;
            }

            throw new \RuntimeException(sprintf('Unresolvable route action parameter [%s].', $name));
        }

        return $resolved;
    }

    /**
     * Standardize the output response to a PSR-7 Response.
     */
    private function formatResponse(mixed $response): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if (is_array($response) || $response instanceof \JsonSerializable) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($response, JSON_THROW_ON_ERROR)
            );
        }

        if (is_string($response)) {
            return new Response(
                200,
                ['Content-Type' => 'text/html'],
                $response
            );
        }

        throw new \RuntimeException('Route action must return a string, array, or Psr\Http\Message\ResponseInterface.');
    }
}
