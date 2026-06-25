<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Support;

/**
 * Contract for objects that can be rendered as a string response.
 */
interface Renderable
{
    /**
     * Get the evaluated contents of the object.
     */
    public function render(): string;
}
