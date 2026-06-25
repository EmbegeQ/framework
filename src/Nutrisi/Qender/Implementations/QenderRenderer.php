<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender\Implementations;

use EmbegeQ\Nutrisi\Qender\QenderRendererInterface;
use EmbegeQ\Nutrisi\Qender\QenderCompilerInterface;
use EmbegeQ\Nutrisi\Qender\ViewFinderInterface;

class QenderRenderer implements QenderRendererInterface
{
    private ViewFinderInterface $finder;
    private QenderCompilerInterface $compiler;

    public function __construct(ViewFinderInterface $finder, QenderCompilerInterface $compiler)
    {
        $this->finder = $finder;
        $this->compiler = $compiler;
    }

    public function render(string $view, array $data = []): string
    {
        $path = $this->finder->find($view);
        $compiled = $this->compiler->compile($path);

        $render = function (string $compiledFile, array $data) {
            extract($data, EXTR_SKIP);
            ob_start();
            include $compiledFile;
            return (string) ob_get_clean();
        };

        return $render($compiled, $data);
    }
}
