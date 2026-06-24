<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Cache;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Service provider for Cache module.
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(CacheManager::class, function (ContainerInterface $container) {
            return new CacheManager($container);
        });

        $app->singleton(CacheInterface::class, function (ContainerInterface $container) {
            return $container->get(CacheManager::class)->store();
        });

        $app->alias(CacheManager::class, 'cache');
        $app->alias(CacheInterface::class, 'cache.store');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
