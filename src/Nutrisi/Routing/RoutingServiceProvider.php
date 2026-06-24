<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Routing;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Routing\RouterInterface;

class RoutingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(Router::class, function () {
            return new Router();
        });

        $app->singleton(RouterInterface::class, function (ContainerInterface $container) {
            return $container->get(Router::class);
        });

        $app->alias(Router::class, 'router');
        $app->alias(RouterInterface::class, 'router.instance');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        // Routing provider requires no additional boot-time actions.
    }
}
