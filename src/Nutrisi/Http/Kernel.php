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
     * Map of request objects to their respective RequestContainers.
     *
     * @var \WeakMap<ServerRequestInterface, \EmbegeQ\Nutrisi\Container\RequestContainer>
     */
    protected \WeakMap $requestContainers;

    /**
     * Create a new HTTP Kernel instance.
     */
    public function __construct(
        protected ApplicationInterface $app,
        protected RouterInterface $router
    ) {
        $this->requestContainers = new \WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Retrieve the active request-scope container from request attributes.
        $container = $request->getAttribute(ContainerInterface::class);

        if (!$container instanceof ContainerInterface) {
            if (isset($this->requestContainers[$request])) {
                $container = $this->requestContainers[$request];
            } else {
                if (!$this->app instanceof \EmbegeQ\Nutrisi\Container\ApplicationContainer) {
                    throw new \RuntimeException('Application container must extend ApplicationContainer.');
                }
                $requestContainer = new \EmbegeQ\Nutrisi\Container\RequestContainer($this->app);
                $this->requestContainers[$request] = $requestContainer;
                $container = $requestContainer;
            }

            // Double bind the container for standard PSR and internal contracts
            $request = $request
                ->withAttribute(ContainerInterface::class, $container)
                ->withAttribute(\EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface::class, $container);

            $container->instance(ServerRequestInterface::class, $request);
        }

        // Wrap the router dispatching in a request handler to act as the fallback.
        $fallback = new class($this->router) implements RequestHandlerInterface {
            public function __construct(private RouterInterface $router) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->router->dispatch($request);
            }
        };

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
    public function send(ResponseInterface $response): void
    {
        if (headers_sent()) {
            return;
        }

        // Set HTTP response code
        http_response_code($response->getStatusCode());

        // Send headers
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Output body
        echo $response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $container = $request->getAttribute(ContainerInterface::class)
            ?? $this->requestContainers[$request]
            ?? null;

        if ($container instanceof \EmbegeQ\Nutrisi\Container\RequestContainer) {
            unset($this->requestContainers[$request]);
        }

        gc_collect_cycles();
    }
}
