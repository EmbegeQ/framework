<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Foundation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Exception Handler contract for the EmbegeQ framework.
 *
 * Provides a unified interface for reporting exceptions (logging)
 * and rendering them into PSR-7 HTTP responses.
 */
interface ExceptionHandlerInterface
{
    /**
     * Report or log an exception.
     *
     * @param  Throwable  $e  The exception to report.
     * @return void
     */
    public function report(Throwable $e): void;

    /**
     * Render an exception into an HTTP response.
     *
     * @param  ServerRequestInterface  $request  The current PSR-7 request.
     * @param  Throwable  $e  The exception to render.
     * @return ResponseInterface
     */
    public function render(ServerRequestInterface $request, Throwable $e): ResponseInterface;
}
