<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http;

use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP Response Wrapper.
 *
 * Provides fluent interface for building PSR-7 responses with convenience methods.
 */
class Response implements ResponseInterface
{
    /**
     * The underlying PSR-7 response.
     *
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * Create a new response instance.
     *
     * @param int $statusCode
     * @param array<string, string|array<string>> $headers
     * @param string|StreamInterface|null $body
     * @param string $protocolVersion
     * @param string|null $reasonPhrase
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        string|StreamInterface|null $body = null,
        string $protocolVersion = '1.1',
        ?string $reasonPhrase = null
    ) {
        $this->response = new Psr7Response(
            $statusCode,
            $headers,
            $body ?? '',
            $protocolVersion,
            $reasonPhrase
        );
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        $this->response = $this->response->withStatus($code);
        return $this;
    }

    /**
     * Set a response header.
     *
     * @param string $name
     * @param string|array<string> $value
     * @return self
     */
    public function setHeader(string $name, string|array $value): self
    {
        $this->response = $this->response->withHeader($name, $value);
        return $this;
    }

    /**
     * Add a response header (preserving existing values).
     *
     * @param string $name
     * @param string|array<string> $value
     * @return self
     */
    public function addHeader(string $name, string|array $value): self
    {
        $this->response = $this->response->withAddedHeader($name, $value);
        return $this;
    }

    /**
     * Set the response body.
     *
     * @param string|StreamInterface $body
     * @return self
     */
    public function setBody(string|StreamInterface $body): self
    {
        if (is_string($body)) {
            $stream = new \Nyholm\Psr7\Stream(\fopen('php://temp', 'r+'));
            $stream->write($body);
            $stream->seek(0);
            $this->response = $this->response->withBody($stream);
        } else {
            $this->response = $this->response->withBody($body);
        }
        return $this;
    }

    /**
     * Get the underlying PSR-7 response.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * PSR-7 ResponseInterface methods (delegation).
     */

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): self
    {
        $this->response = $this->response->withProtocolVersion($version);
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): self
    {
        $this->response = $this->response->withHeader($name, $value);
        return $this;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $this->response = $this->response->withAddedHeader($name, $value);
        return $this;
    }

    public function withoutHeader(string $name): self
    {
        $this->response = $this->response->withoutHeader($name);
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): self
    {
        $this->response = $this->response->withBody($body);
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $this->response = $this->response->withStatus($code, $reasonPhrase);
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }
}
