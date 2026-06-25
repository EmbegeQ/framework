<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles Blade conditional statements.
 */
trait CompilesConditionals
{
    protected function compileIf(string $expression): string
    {
        return "<?php if{$expression}: ?>";
    }

    protected function compileElseif(string $expression): string
    {
        return "<?php elseif{$expression}: ?>";
    }

    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    protected function compileEndif(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileUnless(string $expression): string
    {
        return "<?php if (! {$this->stripParentheses($expression)}): ?>";
    }

    protected function compileEndunless(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileIsset(string $expression): string
    {
        return "<?php if(isset{$expression}): ?>";
    }

    protected function compileEndisset(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileEmpty(string $expression): string
    {
        return "<?php if(empty{$expression}): ?>";
    }

    protected function compileEndempty(): string
    {
        return '<?php endif; ?>';
    }
}
