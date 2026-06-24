<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The HTTP Kernel interface.
 */
interface KernelInterface
{
    /**
     * Handle an incoming HTTP request.
     *
     * @param  ServerRequestInterface  $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Send the given response to the browser (emits headers and body).
     *
     * @param  ResponseInterface  $response
     * @return void
     */
    public function send(ResponseInterface $response): void;

    /**
     * Perform any final actions after the response has been sent.
     *
     * @param  ServerRequestInterface  $request
     * @param  ResponseInterface  $response
     * @return void
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;
}
