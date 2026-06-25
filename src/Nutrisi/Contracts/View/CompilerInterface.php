<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\View;

/**
 * Blade compiler contract.
 */
interface CompilerInterface
{
    /**
     * Compile the view at the given path.
     */
    public function compile(string $path): void;

    /**
     * Determine if the view is expired.
     */
    public function isExpired(string $path): bool;

    /**
     * Get the path to the compiled version of a view.
     */
    public function getCompiledPath(string $path): string;
}
