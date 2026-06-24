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
     * Perform any final actions after the response has been sent.
     *
     * @param  ServerRequestInterface  $request
     * @param  ResponseInterface  $response
     * @return void
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;
}
