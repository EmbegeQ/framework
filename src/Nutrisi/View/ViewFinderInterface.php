<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

/**
 * View finder contract.
 */
interface ViewFinderInterface
{
    /**
     * Find the given view file.
     */
    public function find(string $name): string;

    /**
     * Add a location to the finder.
     *
     * @param  string  $location
     */
    public function addLocation(string $location): void;

    /**
     * Add a namespace hint to the finder.
     *
     * @param  string  $namespace
     * @param  string|array<int, string>  $hints
     */
    public function addNamespace(string $namespace, string|array $hints): void;

    /**
     * Determine if the view exists.
     */
    public function exists(string $name): bool;
}
