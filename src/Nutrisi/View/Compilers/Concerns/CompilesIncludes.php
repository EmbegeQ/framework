<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles Blade include directives.
 */
trait CompilesIncludes
{
    protected function compileInclude(string $expression): string
    {
        return "<?php echo \$__env->make({$expression}, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }

    protected function compileIncludeIf(string $expression): string
    {
        return "<?php if (\$__env->exists({$this->stripParentheses($expression)})) echo \$__env->make({$expression}, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }
}
