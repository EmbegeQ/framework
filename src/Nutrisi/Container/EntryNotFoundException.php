<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when an entry is not found in the container.
 *
 * Implements PSR-11 NotFoundExceptionInterface.
 */
class EntryNotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
}
