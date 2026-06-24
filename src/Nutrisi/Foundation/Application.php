<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Foundation;

use EmbegeQ\Nutrisi\Config\FileLoader;
use EmbegeQ\Nutrisi\Config\Repository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Foundation\ApplicationInterface;

/**
 * The EmbegeQ Application Kernel.
 *
 * Extends the ApplicationContainer with framework lifecycle management:
 * service provider registration and boot sequencing, path resolution,
 * and environment detection.
 *
 * This class is instantiated ONCE per worker process and lives in memory
 * for the entire lifetime of the process.
 */
class Application extends ApplicationContainer implements ApplicationInterface
{
    /**
     * The EmbegeQ framework version.
     */
    private const VERSION = '0.1.0-dev';

    /**
     * The base path for the application installation.
     */
    private string $basePath;

    /**
     * Indicates if the application has been booted.
     */
    private bool $booted = false;

    /**
     * All registered service providers.
     *
     * @var ServiceProviderInterface[]
     */
    private array $serviceProviders = [];

    /**
     * The class names of the loaded service providers (to prevent double registration).
     *
     * @var array<string, true>
     */
    private array $loadedProviders = [];

    /**
     * Create a new EmbegeQ application instance.
     *
     * @param  string  $basePath  The root path of the application.
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    private function registerBaseBindings(): void
    {
        $this->instance('app', $this);
        $this->instance(ContainerInterface::class, $this);
        $this->instance(ApplicationInterface::class, $this);
        $this->instance(\Psr\Container\ContainerInterface::class, $this);
    }

    /**
     * Register the core container aliases.
     *
     * @return void
     */
    private function registerCoreContainerAliases(): void
    {
        $aliases = [
            'config' => RepositoryInterface::class,
        ];

        foreach ($aliases as $alias => $abstract) {
            $this->alias($abstract, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * {@inheritdoc}
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * {@inheritdoc}
     */
    public function environment(): string
    {
        if ($this->bound(RepositoryInterface::class)) {
            /** @var RepositoryInterface $config */
            $config = $this->make(RepositoryInterface::class);

            return (string) $config->get('app.env', 'production');
        }

        return 'production';
    }

    /**
     * {@inheritdoc}
     */
    public function isRunningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * {@inheritdoc}
     */
    public function register(ServiceProviderInterface $provider): void
    {
        $className = $provider::class;

        // Prevent double registration.
        if (isset($this->loadedProviders[$className])) {
            return;
        }

        // Phase 1: Register bindings.
        $provider->register($this);

        $this->serviceProviders[] = $provider;
        $this->loadedProviders[$className] = true;

        // If the application is already booted, boot this provider immediately.
        if ($this->booted) {
            $provider->boot($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Phase 2: Boot all registered providers.
        foreach ($this->serviceProviders as $provider) {
            $provider->boot($this);
        }

        $this->booted = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Load configuration from the config directory and bind the Repository.
     *
     * This is a convenience method called during bootstrapping.
     * It uses FileLoader to scan `config/` and registers the result
     * as a singleton Config Repository.
     *
     * @return void
     */
    public function loadConfiguration(): void
    {
        $configPath = $this->configPath();

        if (is_dir($configPath)) {
            $loader = new FileLoader();
            $items = $loader->load($configPath);
        } else {
            $items = [];
        }

        $repository = new Repository($items);

        $this->instance(RepositoryInterface::class, $repository);
    }
}
