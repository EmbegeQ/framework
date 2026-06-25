<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles Blade loop statements.
 */
trait CompilesLoops
{
    protected function compileForeach(string $expression): string
    {
        return "<?php foreach{$expression}: ?>";
    }

    protected function compileEndforeach(): string
    {
        return '<?php endforeach; ?>';
    }

    protected function compileFor(string $expression): string
    {
        return "<?php for{$expression}: ?>";
    }

    protected function compileEndfor(): string
    {
        return '<?php endfor; ?>';
    }

    protected function compileWhile(string $expression): string
    {
        return "<?php while{$expression}: ?>";
    }

    protected function compileEndwhile(): string
    {
        return '<?php endwhile; ?>';
    }
}
