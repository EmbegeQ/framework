<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\View;

/**
 * View engine contract.
 */
interface EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path  Absolute path to the view file.
     * @param  array<string, mixed>  $data
     */
    public function get(string $path, array $data = []): string;
}
