<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

use EmbegeQ\Nutrisi\Contracts\Support\Htmlable;

/**
 * HTML string wrapper that marks content as safe for rendering.
 */
class HtmlString implements Htmlable, \Stringable
{
    public function __construct(protected string $html) {}

    public function toHtml(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}

/**
 * Escape HTML entities in a value.
 */
function e(mixed $value, bool $doubleEncode = true): string
{
    if ($value instanceof Htmlable) {
        return $value->toHtml();
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
}
