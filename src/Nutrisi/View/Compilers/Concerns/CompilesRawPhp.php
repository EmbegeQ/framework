<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles raw PHP and comments.
 */
trait CompilesRawPhp
{
    protected function compilePhp(string $expression): string
    {
        if ($expression !== '') {
            return "<?php {$this->stripParentheses($expression)}; ?>";
        }

        return '<?php ';
    }

    protected function compileEndphp(): string
    {
        return ' ?>';
    }

    protected function compileComment(?string $expression = null): string
    {
        return '';
    }
}
