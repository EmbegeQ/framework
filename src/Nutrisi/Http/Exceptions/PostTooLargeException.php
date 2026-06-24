<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Exceptions;

use Exception;

/**
 * Post Too Large Exception.
 *
 * Thrown when the request body size exceeds the configured limit
 * (php.ini post_max_size or application-configured limit).
 */
class PostTooLargeException extends Exception
{
    /**
     * Create a new POST too large exception.
     *
     * @param int $limit
     * @param int $actual
     */
    public function __construct(
        private int $limit,
        private int $actual
    ) {
        parent::__construct(
            sprintf(
                'Request body size (%d bytes) exceeds limit (%d bytes).',
                $actual,
                $limit
            )
        );
    }

    /**
     * Get the configured size limit in bytes.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Get the actual request body size in bytes.
     *
     * @return int
     */
    public function getActualSize(): int
    {
        return $this->actual;
    }
}
