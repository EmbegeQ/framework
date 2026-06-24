<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;

/**
 * Service provider for the Database module.
 *
 * Registers the DatabaseManager as a singleton in the application container,
 * along with convenience aliases for the default connection.
 */
class DatabaseServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(DatabaseManager::class, function (ContainerInterface $container) {
            return new DatabaseManager($container);
        });

        $app->singleton(ConnectionResolverInterface::class, function (ContainerInterface $container) {
            return $container->get(DatabaseManager::class);
        });

        $app->alias(DatabaseManager::class, 'db');
        $app->alias(ConnectionResolverInterface::class, 'db.resolver');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
