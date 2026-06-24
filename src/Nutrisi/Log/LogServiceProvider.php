<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Log;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Service provider for Log module.
 */
class LogServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(LogManager::class, function (ContainerInterface $container) {
            return new LogManager($container);
        });

        $app->singleton(LoggerInterface::class, function (ContainerInterface $container) {
            return $container->get(LogManager::class)->channel();
        });

        $app->alias(LogManager::class, 'log');
        $app->alias(LoggerInterface::class, 'logger');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
