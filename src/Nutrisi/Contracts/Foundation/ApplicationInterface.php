<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Foundation;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;

/**
 * Application contract for the EmbegeQ framework.
 *
 * The Application is the root-level kernel that extends the Container
 * with framework lifecycle management: service provider registration,
 * boot sequencing, and environment/path resolution.
 */
interface ApplicationInterface extends ContainerInterface
{
    /**
     * Get the version number of the framework.
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get the base path of the application installation.
     *
     * @param  string  $path  An optional path segment to append.
     * @return string
     */
    public function basePath(string $path = ''): string;

    /**
     * Get the path to the application configuration files.
     *
     * @param  string  $path  An optional path segment to append.
     * @return string
     */
    public function configPath(string $path = ''): string;

    /**
     * Get the current application environment (e.g., 'production', 'local', 'testing').
     *
     * @return string
     */
    public function environment(): string;

    /**
     * Determine if the application is running in the console (CLI).
     *
     * @return bool
     */
    public function isRunningInConsole(): bool;

    /**
     * Register a service provider with the application.
     *
     * @param  ServiceProviderInterface  $provider  The provider instance.
     * @return void
     */
    public function register(ServiceProviderInterface $provider): void;

    /**
     * Boot all registered service providers.
     *
     * This method calls `boot()` on every provider that was registered via `register()`.
     * It MUST only be called once during the application lifecycle.
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Get the path to the bootstrap directory.
     *
     * @param  string  $path  An optional path segment to append.
     * @return string
     */
    public function bootstrapPath(string $path = ''): string;

    /**
     * Register all configured service providers.
     *
     * This merges framework-level providers (DefaultProviders) with
     * application-level providers (bootstrap/providers.php) and registers them all.
     *
     * @return void
     */
    public function registerConfiguredProviders(): void;

    /**
     * Determine if the application has been booted.
     *
     * @return bool
     */
    public function isBooted(): bool;
}
