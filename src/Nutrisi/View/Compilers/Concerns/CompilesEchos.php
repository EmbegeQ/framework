<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers\Concerns;

/**
 * Compiles Blade echo statements.
 */
trait CompilesEchos
{
    protected function compileEchos(string $value): string
    {
        $value = $this->compileRawEchos($value);
        $value = $this->compileEscapedEchos($value);

        return $value;
    }

    protected function compileRawEchos(string $value): string
    {
        $pattern = sprintf('/%s\s*(.+?)\s*%s/s', preg_quote($this->rawTags[0], '/'), preg_quote($this->rawTags[1], '/'));

        return preg_replace_callback($pattern, function (array $matches): string {
            return "<?php echo {$matches[1]}; ?>";
        }, $value) ?? $value;
    }

    protected function compileEscapedEchos(string $value): string
    {
        $pattern = sprintf('/%s\s*(.+?)\s*%s/s', preg_quote($this->contentTags[0], '/'), preg_quote($this->contentTags[1], '/'));

        return preg_replace_callback($pattern, function (array $matches): string {
            return "<?php echo \\EmbegeQ\\Nutrisi\\View\\e({$matches[1]}); ?>";
        }, $value) ?? $value;
    }
}
