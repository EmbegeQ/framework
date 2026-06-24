<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handle CORS Middleware.
 *
 * Handles Cross-Origin Resource Sharing (CORS) requests by:
 * 1. Responding to OPTIONS preflight requests
 * 2. Adding appropriate CORS headers to all responses
 */
class HandleCors implements MiddlewareInterface
{
    /**
     * Allowed origins (domains that can access the resource).
     *
     * @var array<int, string>|string ('*' for all origins)
     */
    private array|string $allowedOrigins;

    /**
     * Allowed HTTP methods.
     *
     * @var array<int, string>
     */
    private array $allowedMethods;

    /**
     * Allowed headers.
     *
     * @var array<int, string>
     */
    private array $allowedHeaders;

    /**
     * Exposed headers.
     *
     * @var array<int, string>
     */
    private array $exposedHeaders;

    /**
     * Whether to allow credentials (cookies, auth headers).
     *
     * @var bool
     */
    private bool $allowCredentials;

    /**
     * Cache duration in seconds.
     *
     * @var int
     */
    private int $maxAge;

    /**
     * Create a new HandleCors middleware instance.
     *
     * @param array<int, string>|string $allowedOrigins
     * @param array<int, string> $allowedMethods
     * @param array<int, string> $allowedHeaders
     * @param array<int, string> $exposedHeaders
     * @param bool $allowCredentials
     * @param int $maxAge
     */
    public function __construct(
        array|string $allowedOrigins = '*',
        array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['*'],
        array $exposedHeaders = [],
        bool $allowCredentials = false,
        int $maxAge = 3600
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->exposedHeaders = $exposedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
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
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }

        // Add CORS headers to the response
        $response = $handler->handle($request);
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Handle preflight OPTIONS request.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    private function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = new \Nyholm\Psr7\Response(200);

        $origin = $request->getHeaderLine('Origin');
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        // Allow requested methods
        $response = $response->withHeader(
            'Access-Control-Allow-Methods',
            implode(', ', $this->allowedMethods)
        );

        // Allow requested headers (or all if * specified)
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        if ($requestHeaders) {
            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                in_array('*', $this->allowedHeaders) ? $requestHeaders : implode(', ', $this->allowedHeaders)
            );
        }

        // Set cache duration
        if ($this->maxAge > 0) {
            $response = $response->withHeader(
                'Access-Control-Max-Age',
                (string)$this->maxAge
            );
        }

        // Allow credentials if configured
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Add CORS headers to response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function addCorsHeaders(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $origin = $request->getHeaderLine('Origin');
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        // Add exposed headers
        if (!empty($this->exposedHeaders)) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->exposedHeaders)
            );
        }

        // Allow credentials if configured
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Check if the origin is allowed.
     *
     * @param string $origin
     * @return bool
     */
    private function isOriginAllowed(string $origin): bool
    {
        if ($this->allowedOrigins === '*') {
            return true;
        }

        if (is_array($this->allowedOrigins)) {
            return in_array($origin, $this->allowedOrigins, true);
        }

        return false;
    }
}
