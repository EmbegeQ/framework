<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Routing;

use EmbegeQ\Nutrisi\Contracts\Routing\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Routing Middleware.
 *
 * Dispatches the request through the Router. Usually placed as the final
 * middleware in the global pipeline.
 */
class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * Create a new RoutingMiddleware instance.
     */
    public function __construct(private RouterInterface $router) {}

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->router->dispatch($request);
    }
}
