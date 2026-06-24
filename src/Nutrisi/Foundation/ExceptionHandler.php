<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Foundation;

use EmbegeQ\Nutrisi\Contracts\Foundation\ExceptionHandlerInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Default Exception Handler for the EmbegeQ framework.
 *
 * Reports exceptions to a PSR-3 logger (if available) and renders
 * them as PSR-7 HTTP responses. In production, details are hidden;
 * in development, a full stack trace is rendered.
 */
class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * The PSR-3 logger instance (optional).
     */
    private ?LoggerInterface $logger;

    /**
     * The current environment ('production', 'local', 'testing').
     */
    private string $environment;

    /**
     * Create a new exception handler instance.
     *
     * @param  LoggerInterface|null  $logger  An optional PSR-3 logger for reporting.
     * @param  string  $environment  The current application environment.
     */
    public function __construct(?LoggerInterface $logger = null, string $environment = 'production')
    {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function report(Throwable $e): void
    {
        $this->logger?->error($e->getMessage(), [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ServerRequestInterface $request, Throwable $e): ResponseInterface
    {
        $statusCode = $this->resolveStatusCode($e);

        $body = $this->buildResponseBody($e, $statusCode);

        return new Response(
            status: $statusCode,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Resolve an appropriate HTTP status code from the exception.
     *
     * @param  Throwable  $e  The exception.
     * @return int
     */
    private function resolveStatusCode(Throwable $e): int
    {
        $code = $e->getCode();

        if (is_int($code) && $code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    /**
     * Build the response body array.
     *
     * In production, only the message and status code are included.
     * In development, the full exception trace is included.
     *
     * @param  Throwable  $e  The exception.
     * @param  int  $statusCode  The HTTP status code.
     * @return array<string, mixed>
     */
    private function buildResponseBody(Throwable $e, int $statusCode): array
    {
        $body = [
            'error' => true,
            'status' => $statusCode,
            'message' => $this->environment === 'production'
                ? 'Internal Server Error'
                : $e->getMessage(),
        ];

        if ($this->environment !== 'production') {
            $body['exception'] = $e::class;
            $body['file'] = $e->getFile();
            $body['line'] = $e->getLine();
            $body['trace'] = array_map(
                fn (array $frame): array => [
                    'file' => $frame['file'] ?? 'unknown',
                    'line' => $frame['line'] ?? 0,
                    'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'],
                ],
                $e->getTrace(),
            );
        }

        return $body;
    }
}
