<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http;

use Nyholm\Psr7\Stream;

/**
 * Redirect Response Helper.
 *
 * Provides a convenient way to create redirect responses.
 */
class RedirectResponse extends Response
{
    /**
     * Create a new redirect response.
     *
     * @param string $location
     * @param int $statusCode (301, 302, 303, 307, 308)
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        string $location,
        int $statusCode = 302,
        array $headers = []
    ) {
        $stream = new Stream(\fopen('php://temp', 'r+'));
        $stream->seek(0);

        parent::__construct(
            statusCode: $statusCode,
            headers: array_merge(
                $headers,
                ['Location' => $location]
            ),
            body: $stream
        );
    }

    /**
     * Create a temporary redirect (HTTP 302).
     *
     * @param string $location
     * @return self
     */
    public static function temporary(string $location): self
    {
        return new self($location, 302);
    }

    /**
     * Create a permanent redirect (HTTP 301).
     *
     * @param string $location
     * @return self
     */
    public static function permanent(string $location): self
    {
        return new self($location, 301);
    }

    /**
     * Create a see-other redirect (HTTP 303).
     *
     * @param string $location
     * @return self
     */
    public static function seeOther(string $location): self
    {
        return new self($location, 303);
    }
}
