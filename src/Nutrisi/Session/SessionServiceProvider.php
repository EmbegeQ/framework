<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Session;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;

/**
 * Service provider for Session module.
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(SessionManager::class, function (ContainerInterface $container) {
            return new SessionManager($container);
        });

        $app->alias(SessionManager::class, 'session.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
