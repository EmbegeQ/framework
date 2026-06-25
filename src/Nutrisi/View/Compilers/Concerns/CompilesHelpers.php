<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles Blade helper directives.
 */
trait CompilesHelpers
{
    protected function compileCsrf(): string
    {
        return '<?php echo \'<input type="hidden" name="_token" value="\' . htmlspecialchars((string) ($csrf ?? \'\'), ENT_QUOTES, \'UTF-8\') . \'">\'; ?>';
    }

    protected function compileVite(?string $arguments = null): string
    {
        $arguments ??= '()';

        return "<?php echo \$__container->get(\\EmbegeQ\\Nutrisi\\Foundation\\Vite::class){$arguments}; ?>";
    }

    protected function compileVerbatim(): string
    {
        return '<?php $__verbatimStarted = true; ob_start(); ?>';
    }

    protected function compileEndverbatim(): string
    {
        return '<?php echo ob_get_clean(); $__verbatimStarted = false; ?>';
    }
}
