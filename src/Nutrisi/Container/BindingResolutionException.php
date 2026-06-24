<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Container;

/**
 * Exception thrown when the container cannot resolve a binding.
 *
 * Common causes:
 * - Attempting to autowire an interface that has no registered binding.
 * - Circular dependency detected during Reflection resolution.
 * - A constructor parameter has no type hint and no default value.
 */
class BindingResolutionException extends \RuntimeException
{
}
