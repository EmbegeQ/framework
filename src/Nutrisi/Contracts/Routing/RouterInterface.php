<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The Router interface.
 */
interface RouterInterface
{
    /**
     * Add a GET route.
     *
     * @param  string  $uri
     * @param  array{0:class-string, 1:string}|string|\Closure  $action
     * @return void
     */
    public function get(string $uri, array|string|\Closure $action): void;

    /**
     * Add a POST route.
     *
     * @param  string  $uri
     * @param  array{0:class-string, 1:string}|string|\Closure  $action
     * @return void
     */
    public function post(string $uri, array|string|\Closure $action): void;

    /**
     * Add a PUT route.
     *
     * @param  string  $uri
     * @param  array{0:class-string, 1:string}|string|\Closure  $action
     * @return void
     */
    public function put(string $uri, array|string|\Closure $action): void;

    /**
     * Add a DELETE route.
     *
     * @param  string  $uri
     * @param  array{0:class-string, 1:string}|string|\Closure  $action
     * @return void
     */
    public function delete(string $uri, array|string|\Closure $action): void;

    /**
     * Register a route group with shared attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  callable  $callback
     * @return void
     */
    public function group(array $attributes, callable $callback): void;

    /**
     * Dispatch the request to the matching route.
     *
     * @param  ServerRequestInterface  $request
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
