<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\View;

use EmbegeQ\Nutrisi\Contracts\Support\Htmlable;
use EmbegeQ\Nutrisi\Contracts\Support\Renderable;

/**
 * Rendered view contract.
 */
interface ViewInterface extends Htmlable, Renderable
{
    /**
     * Get the name of the view.
     */
    public function name(): string;

    /**
     * Get the path to the view file.
     */
    public function path(): string;

    /**
     * Get all of the view data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;
}
