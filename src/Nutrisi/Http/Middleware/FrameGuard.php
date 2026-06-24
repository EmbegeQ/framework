<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Frame Guard Middleware.
 *
 * Prevents clickjacking attacks by setting the X-Frame-Options header.
 * This tells browsers not to display this page within a frame or iframe.
 */
class FrameGuard implements MiddlewareInterface
{
    /**
     * The X-Frame-Options value.
     *
     * @var string (DENY, SAMEORIGIN, ALLOW-FROM, or ALLOWALL)
     */
    private string $option;

    /**
     * Create a new FrameGuard middleware instance.
     *
     * @param string $option (default: DENY)
     */
    public function __construct(string $option = 'DENY')
    {
        $this->option = $option;
    }

    /**
     * Process the request through the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Add X-Frame-Options header to prevent clickjacking
        return $response->withHeader('X-Frame-Options', $this->option);
    }
}
