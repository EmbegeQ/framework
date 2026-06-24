<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Config;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        if (!$app->bound(RepositoryInterface::class)) {
            $app->singleton(RepositoryInterface::class, function () {
                return new Repository([]);
            });
        }

        $app->alias(RepositoryInterface::class, 'config');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        // Intentionally left blank; config is initialized in Application bootstrap.
    }
}
