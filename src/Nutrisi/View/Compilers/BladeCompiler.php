<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Compilers;

use EmbegeQ\Nutrisi\Contracts\View\CompilerInterface;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesConditionals;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesEchos;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesHelpers;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesIncludes;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesLayouts;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesLoops;
use EmbegeQ\Nutrisi\View\Compilers\Concerns\CompilesRawPhp;
use EmbegeQ\Nutrisi\View\Filesystem;

/**
 * Blade-inspired template compiler for EmbegeQ.
 *
 * Compiles .blade.php templates into plain PHP for execution.
 * Syntax is intentionally familiar to Laravel Blade developers.
 */
class BladeCompiler extends Compiler implements CompilerInterface
{
    use CompilesConditionals,
        CompilesEchos,
        CompilesHelpers,
        CompilesIncludes,
        CompilesLayouts,
        CompilesLoops,
        CompilesRawPhp;

    /** @var array<int, string> */
    protected array $rawTags = ['{!!', '!!}'];

    /** @var array<int, string> */
    protected array $contentTags = ['{{', '}}'];

    /** @var array<string, callable> */
    protected array $customDirectives = [];

    protected string $path = '';

    public function __construct(
        Filesystem $files,
        string $cachePath,
        string $basePath = '',
        bool $shouldCache = true,
        bool $shouldCheckTimestamps = true,
    ) {
        parent::__construct($files, $cachePath, $basePath, $shouldCache, $shouldCheckTimestamps);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(string $path): void
    {
        $this->path = $path;
        $contents = $this->compileString($this->files->get($path));

        if (!$this->files->put($this->getCompiledPath($path), $contents)) {
            throw new \RuntimeException('Unable to write compiled view.');
        }
    }

    public function compileString(string $value): string
    {
        $value = $this->storeVerbatimBlocks($value);
        $value = $this->compileComments($value);
        $value = $this->compileStatements($value);
        $value = $this->compileEchos($value);
        $value = $this->restoreVerbatimBlocks($value);

        return $value;
    }

    /**
     * @var array<int, string>
     */
    protected array $verbatimBlocks = [];

    protected function storeVerbatimBlocks(string $value): string
    {
        return preg_replace_callback('/@verbatim\s*(.*?)\s*@endverbatim/s', function (array $matches): string {
            $this->verbatimBlocks[] = $matches[1];

            return '@__verbatim__' . (count($this->verbatimBlocks) - 1) . '__@';
        }, $value) ?? $value;
    }

    protected function restoreVerbatimBlocks(string $value): string
    {
        return preg_replace_callback('/@__verbatim__(\d+)__@/', function (array $matches): string {
            return $this->verbatimBlocks[(int) $matches[1]] ?? '';
        }, $value) ?? $value;
    }

    protected function compileComments(string $value): string
    {
        return preg_replace('/{{--[\s\S]*?--}}/u', '', $value) ?? $value;
    }

    protected function compileStatements(string $value): string
    {
        return preg_replace_callback(
            '/(?<!\w)@(\w+)(?:\s*\((.*?)\))?/s',
            function (array $matches): string {
                $method = 'compile' . ucfirst($matches[1]);

                if (method_exists($this, $method)) {
                    $expression = isset($matches[2]) ? '(' . $matches[2] . ')' : '';

                    return $this->{$method}($expression);
                }

                if (isset($this->customDirectives[$matches[1]])) {
                    $expression = isset($matches[2]) ? '(' . $matches[2] . ')' : '';

                    return ($this->customDirectives[$matches[1]])(trim($expression, '()'));
                }

                return $matches[0];
            },
            $value
        ) ?? $value;
    }

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    protected function stripParentheses(string $expression): string
    {
        return trim($expression, '()');
    }
}
