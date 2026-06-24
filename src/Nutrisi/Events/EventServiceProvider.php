<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Events;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Events\DispatcherInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;

/**
 * Service provider for the Events module.
 */
class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(Dispatcher::class, function (ContainerInterface $container) {
            return new Dispatcher($container);
        });

        $app->singleton(DispatcherInterface::class, function (ContainerInterface $container) {
            return $container->get(Dispatcher::class);
        });

        $app->singleton(PsrEventDispatcherInterface::class, function (ContainerInterface $container) {
            return $container->get(Dispatcher::class);
        });

        $app->alias(Dispatcher::class, 'events');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
