<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Queue\QueueInterface;

class QueueServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(QueueManager::class, function (ContainerInterface $container) {
            return new QueueManager($container);
        });

        $app->singleton(QueueInterface::class, function (ContainerInterface $container) {
            return $container->get(QueueManager::class)->connection();
        });

        $app->singleton(Worker::class, function (ContainerInterface $container) {
            return new Worker($container->get(QueueManager::class));
        });

        $app->alias(QueueManager::class, 'queue');
        $app->alias(QueueInterface::class, 'queue.connection');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
