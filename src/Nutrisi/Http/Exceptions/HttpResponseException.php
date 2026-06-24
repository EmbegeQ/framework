<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response Exception.
 *
 * Allows middleware or controllers to throw a PSR-7 response directly,
 * bypassing normal response handling.
 */
class HttpResponseException extends Exception
{
    /**
     * Create a new HTTP response exception instance.
     *
     * @param ResponseInterface $response
     */
    public function __construct(
        private ResponseInterface $response
    ) {
        parent::__construct();
    }

    /**
     * Get the underlying response.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
