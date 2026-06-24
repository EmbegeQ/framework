<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Concerns;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interacts With Content Types.
 *
 * Provides methods for detecting and handling content types.
 */
trait InteractsWithContentTypes
{
    /**
     * Determine if the request is sending JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->contentType() === 'application/json'
            || str_contains($this->contentType() ?? '', '+json');
    }

    /**
     * Get the content type of the request.
     *
     * @return string|null
     */
    public function contentType(): ?string
    {
        if ($this instanceof ServerRequestInterface) {
            $contentType = $this->getHeaderLine('Content-Type');
            if ($contentType) {
                return explode(';', $contentType)[0];
            }
        }
        return null;
    }

    /**
     * Determine if the request accepts JSON.
     *
     * @return bool
     */
    public function acceptsJson(): bool
    {
        $accept = $this->getAcceptHeader();
        return str_contains($accept, 'application/json')
            || str_contains($accept, 'application/*+json')
            || str_contains($accept, '*/*');
    }

    /**
     * Determine if the request accepts HTML.
     *
     * @return bool
     */
    public function acceptsHtml(): bool
    {
        $accept = $this->getAcceptHeader();
        return str_contains($accept, 'text/html')
            || str_contains($accept, '*/*');
    }

    /**
     * Get the Accept header.
     *
     * @return string
     */
    private function getAcceptHeader(): string
    {
        if ($this instanceof ServerRequestInterface) {
            return $this->getHeaderLine('Accept') ?? '';
        }
        return '';
    }

    /**
     * Determine if the request is XmlHttpRequest (AJAX).
     *
     * @return bool
     */
    public function isXmlHttpRequest(): bool
    {
        if ($this instanceof ServerRequestInterface) {
            return strtolower($this->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
        }
        return false;
    }
}
