<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles Blade layout directives.
 */
trait CompilesLayouts
{
    protected function compileExtends(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->make({$expression}, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }

    protected function compileSection(string $expression): string
    {
        return "<?php \$__env->startSection{$expression}; ?>";
    }

    protected function compileEndsection(): string
    {
        return '<?php $__env->stopSection(); ?>';
    }

    protected function compileYield(string $expression): string
    {
        return "<?php echo \$__env->yieldContent{$expression}; ?>";
    }

    protected function compileParent(): string
    {
        return "<?php echo \$__env->parentPlaceholder(); ?>";
    }

    protected function compileStopSection(): string
    {
        return '<?php $__env->stopSection(); ?>';
    }

    protected function compileShow(): string
    {
        return '<?php echo $__env->yieldSection(); ?>';
    }

    protected function compilePush(string $expression): string
    {
        return "<?php \$__env->startPush{$expression}; ?>";
    }

    protected function compileEndpush(): string
    {
        return '<?php $__env->stopPush(); ?>';
    }
}
