<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\View;

/**
 * View factory contract.
 */
interface FactoryInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $view
     * @param  array<string, mixed>  $data
     */
    public function make(string $view, array $data = []): ViewInterface;

    /**
     * Determine if a view exists.
     */
    public function exists(string $view): bool;

    /**
     * Add a piece of shared data to the environment.
     *
     * @param  array<string, mixed>|string  $key
     * @param  mixed  $value
     */
    public function share(array|string $key, mixed $value = null): mixed;
}
