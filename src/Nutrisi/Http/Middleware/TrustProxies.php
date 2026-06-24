<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Trust Proxies Middleware.
 *
 * Safely extracts the client IP and protocol from reverse proxy headers
 * (X-Forwarded-For, X-Forwarded-Proto, X-Forwarded-Host).
 *
 * SECURITY: Only trust headers from configured proxy IPs.
 */
class TrustProxies implements MiddlewareInterface
{
    /**
     * List of trusted proxy IP addresses.
     *
     * @var array<int, string>
     */
    private array $trustedProxies = [];

    /**
     * List of headers to trust from proxies.
     *
     * @var array<int, string>
     */
    private array $trustedHeaders = [
        'X-Forwarded-For',
        'X-Forwarded-Proto',
        'X-Forwarded-Host',
        'X-Forwarded-Port',
    ];

    /**
     * Create a new TrustProxies middleware instance.
     *
     * @param array<int, string> $trustedProxies IP addresses to trust
     * @param array<int, string>|null $trustedHeaders Headers to trust
     */
    public function __construct(
        array $trustedProxies = [],
        ?array $trustedHeaders = null
    ) {
        $this->trustedProxies = $trustedProxies;
        if ($trustedHeaders !== null) {
            $this->trustedHeaders = $trustedHeaders;
        }
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
        // If no trusted proxies configured, pass through
        if (empty($this->trustedProxies)) {
            return $handler->handle($request);
        }

        // Get the client IP from the server params (remote address)
        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        // Check if the direct connection is from a trusted proxy
        if ($clientIp && $this->isTrustedProxy($clientIp)) {
            $request = $this->extractForwardedHeaders($request);
        }

        return $handler->handle($request);
    }

    /**
     * Check if the given IP is a trusted proxy.
     *
     * @param string $ip
     * @return bool
     */
    private function isTrustedProxy(string $ip): bool
    {
        return in_array($ip, $this->trustedProxies, true);
    }

    /**
     * Extract forwarded headers from the request.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function extractForwardedHeaders(
        ServerRequestInterface $request
    ): ServerRequestInterface {
        $serverParams = $request->getServerParams();

        // Extract client IP from X-Forwarded-For
        if (in_array('X-Forwarded-For', $this->trustedHeaders)) {
            $forwarded = $request->getHeaderLine('X-Forwarded-For');
            if ($forwarded) {
                $ips = array_map('trim', explode(',', $forwarded));
                $clientIp = reset($ips);
                if ($clientIp) {
                    $serverParams['REMOTE_ADDR'] = $clientIp;
                }
            }
        }

        // Extract protocol from X-Forwarded-Proto
        if (in_array('X-Forwarded-Proto', $this->trustedHeaders)) {
            $proto = $request->getHeaderLine('X-Forwarded-Proto');
            if ($proto) {
                $serverParams['REQUEST_SCHEME'] = $proto;
                $serverParams['wsgi.url_scheme'] = $proto;
            }
        }

        // Extract host from X-Forwarded-Host
        if (in_array('X-Forwarded-Host', $this->trustedHeaders)) {
            $host = $request->getHeaderLine('X-Forwarded-Host');
            if ($host) {
                $serverParams['HTTP_HOST'] = $host;
            }
        }

        // Extract port from X-Forwarded-Port
        if (in_array('X-Forwarded-Port', $this->trustedHeaders)) {
            $port = $request->getHeaderLine('X-Forwarded-Port');
            if ($port) {
                $serverParams['SERVER_PORT'] = $port;
            }
        }

        return $request->withServerParams($serverParams);
    }
}
