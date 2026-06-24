<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Validation;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Validation\ValidatorFactoryInterface;

class ValidationServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(ValidatorFactory::class, function (ContainerInterface $container) {
            return new ValidatorFactory($container);
        });

        $app->singleton(ValidatorFactoryInterface::class, function (ContainerInterface $container) {
            return $container->get(ValidatorFactory::class);
        });

        $app->alias(ValidatorFactory::class, 'validator');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
