<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The HTTP Request capture factory.
 */
class Request
{
    /**
     * Capture the current HTTP request from global PHP variables.
     *
     * @return ServerRequestInterface
     */
    public static function capture(): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $creator->fromGlobals();
    }
}
