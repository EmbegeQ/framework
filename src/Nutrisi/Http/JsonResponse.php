<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http;

use Nyholm\Psr7\Stream;

/**
 * JSON Response Helper.
 *
 * Provides a convenient way to create JSON responses.
 */
class JsonResponse extends Response
{
    /**
     * Create a new JSON response.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array<string, string|array<string>> $headers
     * @param int $jsonOptions
     */
    public function __construct(
        mixed $data = [],
        int $statusCode = 200,
        array $headers = [],
        int $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) {
        $json = json_encode($data, $jsonOptions);
        if ($json === false) {
            $json = json_encode(['error' => 'JSON encoding failed']);
        }

        $stream = new Stream(\fopen('php://temp', 'r+'));
        $stream->write($json);
        $stream->seek(0);

        parent::__construct(
            statusCode: $statusCode,
            headers: array_merge(
                $headers,
                ['Content-Type' => 'application/json']
            ),
            body: $stream
        );
    }

    /**
     * Create a JSON response with data.
     *
     * @param mixed $data
     * @param int $statusCode
     * @return self
     */
    public static function create(
        mixed $data = [],
        int $statusCode = 200
    ): self {
        return new self($data, $statusCode);
    }
}
