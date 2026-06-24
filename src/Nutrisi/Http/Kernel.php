<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http;

use EmbegeQ\Nutrisi\Contracts\Http\KernelInterface;
use EmbegeQ\Nutrisi\Contracts\Foundation\ApplicationInterface;
use EmbegeQ\Nutrisi\Contracts\Routing\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The HTTP Kernel.
 *
 * Coordinates execution of global middleware stacks and dispatches the request
 * to the Router.
 */
class Kernel implements KernelInterface
{
    /**
     * The application's global middleware stack.
     *
     * @var array<int, string|\Psr\Http\Server\MiddlewareInterface|callable>
     */
    protected array $middleware = [];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, string|\Psr\Http\Server\MiddlewareInterface|callable>>
     */
    protected array $middlewareGroups = [];

    /**
     * The application's route middleware aliases.
     *
     * @var array<string, string|\Psr\Http\Server\MiddlewareInterface|callable>
     */
    protected array $routeMiddleware = [];

    /**
     * Create a new HTTP Kernel instance.
     */
    public function __construct(
        protected ApplicationInterface $app,
        protected RouterInterface $router
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Wrap the router dispatching in a request handler to act as the fallback.
        $fallback = new class($this->router) implements RequestHandlerInterface {
            public function __construct(private RouterInterface $router) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->router->dispatch($request);
            }
        };

        // Retrieve the active request-scope container from request attributes,
        // falling back to the application container.
        $container = $request->getAttribute(ContainerInterface::class) ?? $this->app;

        $dispatcher = new MiddlewareDispatcher(
            $container,
            $this->middleware,
            $fallback
        );

        return $dispatcher->handle($request);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        // Reserved for post-response cleanup tasks.
    }
}
