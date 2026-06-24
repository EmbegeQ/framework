<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Container;

/**
 * Service Provider contract for the EmbegeQ framework.
 *
 * Follows the two-phase lifecycle pattern:
 * 1. `register()` - Bind services into the container. No resolved services should be used here.
 * 2. `boot()` - Post-registration logic. All providers have been registered, so resolved services are safe to use.
 */
interface ServiceProviderInterface
{
    /**
     * Register bindings into the container.
     *
     * This method is called during the registration phase. You should only bind
     * things into the container here. Do NOT attempt to resolve any services,
     * as other providers may not have been registered yet.
     *
     * @param  ContainerInterface  $app  The application container.
     * @return void
     */
    public function register(ContainerInterface $app): void;

    /**
     * Bootstrap any application services.
     *
     * This method is called after ALL service providers have been registered.
     * You may resolve services from the container and perform post-registration
     * setup here (e.g., registering event listeners, publishing config files).
     *
     * @param  ContainerInterface  $app  The application container.
     * @return void
     */
    public function boot(ContainerInterface $app): void;
}
