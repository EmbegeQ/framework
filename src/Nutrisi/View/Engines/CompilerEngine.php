<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Engines;

use EmbegeQ\Nutrisi\Contracts\View\CompilerInterface;
use EmbegeQ\Nutrisi\Contracts\View\EngineInterface;
use EmbegeQ\Nutrisi\View\Filesystem;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\View\FactoryInterface as ViewFactoryInterface;
use EmbegeQ\Nutrisi\View\Factory;

/**
 * Blade compiler engine.
 */
class CompilerEngine implements EngineInterface
{
    public function __construct(
        protected CompilerInterface $compiler,
        protected Filesystem $files,
        protected ViewFactoryInterface $factory,
        protected ContainerInterface $container,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $path, array $data = []): string
    {
        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        $compiled = $this->compiler->getCompiledPath($path);

        return $this->evaluatePath($compiled, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function evaluatePath(string $path, array $data): string
    {
        ob_start();

        $__env = $this->factory;
        $__container = $this->container;

        extract($data, EXTR_SKIP);

        try {
            include $path;
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        $contents = ob_get_clean();

        return $contents === false ? '' : $contents;
    }
}
