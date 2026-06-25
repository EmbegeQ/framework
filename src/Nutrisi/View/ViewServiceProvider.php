<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Foundation\ApplicationInterface;
use EmbegeQ\Nutrisi\Contracts\View\CompilerInterface;
use EmbegeQ\Nutrisi\Contracts\View\FactoryInterface;
use EmbegeQ\Nutrisi\Foundation\Vite;
use EmbegeQ\Nutrisi\View\Compilers\BladeCompiler;
use EmbegeQ\Nutrisi\View\Engines\CompilerEngine;
use EmbegeQ\Nutrisi\View\Engines\EngineResolver;
use EmbegeQ\Nutrisi\View\Engines\PhpEngine;

/**
 * Service provider for the View / Blade module.
 */
class ViewServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $this->registerFilesystem($app);
        $this->registerEngineResolver($app);
        $this->registerBladeCompiler($app);
        $this->registerViewFinder($app);
        $this->registerFactory($app);
        $this->registerVite($app);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }

    protected function registerFilesystem(ContainerInterface $app): void
    {
        $app->singleton(Filesystem::class, fn (): Filesystem => new Filesystem());
    }

    protected function registerEngineResolver(ContainerInterface $app): void
    {
        $app->singleton(EngineResolver::class, function (ContainerInterface $container): EngineResolver {
            $resolver = new EngineResolver();

            $resolver->register('blade', fn (): CompilerEngine => new CompilerEngine(
                $container->get(CompilerInterface::class),
                $container->get(Filesystem::class),
                $container->get(FactoryInterface::class),
                $container,
            ));

            $resolver->register('php', fn (): PhpEngine => new PhpEngine(
                $container->get(Filesystem::class),
            ));

            return $resolver;
        });
    }

    protected function registerBladeCompiler(ContainerInterface $app): void
    {
        $app->singleton(CompilerInterface::class, function (ContainerInterface $container): BladeCompiler {
            /** @var RepositoryInterface $config */
            $config = $container->get(RepositoryInterface::class);
            /** @var ApplicationInterface $application */
            $application = $container->get(ApplicationInterface::class);

            return new BladeCompiler(
                $container->get(Filesystem::class),
                (string) $config->get('view.compiled', $application->basePath('storage/framework/views')),
                $application->basePath(),
                (bool) $config->get('view.cache', true),
                (bool) $config->get('view.check_cache_timestamps', true),
            );
        });

        $app->alias(CompilerInterface::class, 'blade.compiler');
    }

    protected function registerViewFinder(ContainerInterface $app): void
    {
        $app->singleton(ViewFinderInterface::class, function (ContainerInterface $container): FileViewFinder {
            /** @var RepositoryInterface $config */
            $config = $container->get(RepositoryInterface::class);

            /** @var array<int, string> $paths */
            $paths = $config->get('view.paths', []);

            return new FileViewFinder(
                $container->get(Filesystem::class),
                $paths,
            );
        });

        $app->alias(ViewFinderInterface::class, 'view.finder');
    }

    protected function registerFactory(ContainerInterface $app): void
    {
        $app->singleton(FactoryInterface::class, function (ContainerInterface $container): Factory {
            return new Factory(
                $container->get(EngineResolver::class),
                $container->get(ViewFinderInterface::class),
            );
        });

        $app->alias(FactoryInterface::class, 'view');
    }

    protected function registerVite(ContainerInterface $app): void
    {
        $app->singleton(Vite::class, function (ContainerInterface $container): Vite {
            /** @var ApplicationInterface $application */
            $application = $container->get(ApplicationInterface::class);

            return (new Vite())->setBasePath($application->basePath());
        });

        $app->alias(Vite::class, 'vite');
    }
}
