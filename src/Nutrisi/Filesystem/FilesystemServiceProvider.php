<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Filesystem;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Filesystem\FilesystemInterface;

/**
 * Service provider for Filesystem module.
 */
class FilesystemServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(FilesystemManager::class, function (ContainerInterface $container) {
            return new FilesystemManager($container);
        });

        $app->singleton(FilesystemInterface::class, function (ContainerInterface $container) {
            return $container->get(FilesystemManager::class)->disk();
        });

        $app->alias(FilesystemManager::class, 'filesystem');
        $app->alias(FilesystemInterface::class, 'filesystem.disk');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
