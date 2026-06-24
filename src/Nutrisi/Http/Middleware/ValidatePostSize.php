<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Middleware;

use EmbegeQ\Nutrisi\Http\Exceptions\PostTooLargeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Validate Post Size Middleware.
 *
 * Validates that the request body size does not exceed configured limits.
 * This prevents oversized POST/PUT/PATCH requests from consuming resources.
 */
class ValidatePostSize implements MiddlewareInterface
{
    /**
     * Maximum allowed post size in bytes.
     * If null, uses php.ini post_max_size.
     *
     * @var int|null
     */
    private ?int $maxPostSize;

    /**
     * Create a new ValidatePostSize middleware instance.
     *
     * @param int|null $maxPostSize Maximum size in bytes (null = php.ini limit)
     */
    public function __construct(?int $maxPostSize = null)
    {
        $this->maxPostSize = $maxPostSize ?? $this->parsePhpIniSize(ini_get('post_max_size'));
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
        $contentLength = (int)$request->getHeaderLine('Content-Length');

        if ($contentLength > 0 && $this->maxPostSize > 0 && $contentLength > $this->maxPostSize) {
            throw new PostTooLargeException($this->maxPostSize, $contentLength);
        }

        return $handler->handle($request);
    }

    /**
     * Parse PHP ini size format to bytes.
     *
     * @param string|int $value
     * @return int
     */
    private function parsePhpIniSize(string|int $value): int
    {
        $value = (string)$value;
        $matches = [];

        if (preg_match('/^(\d+)([KMG])$/i', $value, $matches)) {
            $size = (int)$matches[1];
            $unit = strtoupper($matches[2]);

            return match ($unit) {
                'K' => $size * 1024,
                'M' => $size * 1024 * 1024,
                'G' => $size * 1024 * 1024 * 1024,
                default => $size,
            };
        }

        return (int)$value;
    }
}
