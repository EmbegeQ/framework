<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Support;

/**
 * Contract for objects that can be rendered as HTML.
 */
interface Htmlable
{
    /**
     * Get content as a string of HTML.
     */
    public function toHtml(): string;
}
