<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Immutable PSR-15 Middleware Dispatcher.
 *
 * Runs a stack of middlewares in FIFO order. Re-instantiates itself
 * for each step in the chain to maintain state isolation and safety.
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @param  ContainerInterface  $container  The active container to resolve middleware strings.
     * @param  array<int, string|MiddlewareInterface|callable>  $middlewares  The middleware stack.
     * @param  RequestHandlerInterface  $fallbackHandler  The final handler (e.g. Router).
     * @param  int  $index  The current middleware index.
     */
    public function __construct(
        private ContainerInterface $container,
        private array $middlewares,
        private RequestHandlerInterface $fallbackHandler,
        private int $index = 0
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->index >= count($this->middlewares)) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = $this->middlewares[$this->index];
        $next = new self($this->container, $this->middlewares, $this->fallbackHandler, $this->index + 1);

        // Resolve middleware class name if it is a string.
        if (is_string($middleware)) {
            $middleware = $this->container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $next);
        }

        if (is_callable($middleware)) {
            return $middleware($request, $next);
        }

        throw new \RuntimeException(sprintf(
            'Invalid middleware type resolved at index %d. Expected string, callable, or MiddlewareInterface.',
            $this->index
        ));
    }
}
