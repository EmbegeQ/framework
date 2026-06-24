<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Exceptions;

use Exception;

/**
 * Throttle Requests Exception.
 *
 * Thrown when a client exceeds the configured rate limit.
 */
class ThrottleRequestsException extends Exception
{
    /**
     * The number of seconds to wait before retrying.
     *
     * @var int
     */
    private int $retryAfter;

    /**
     * Create a new throttle requests exception.
     *
     * @param int $retryAfter
     * @param string|null $message
     */
    public function __construct(
        int $retryAfter,
        ?string $message = null
    ) {
        $this->retryAfter = $retryAfter;
        parent::__construct(
            $message ?? sprintf('Too many requests. Retry after %d seconds.', $retryAfter)
        );
    }

    /**
     * Get the number of seconds to wait before retrying.
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
