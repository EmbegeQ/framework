<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Session;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Session\SessionInterface;
use SessionHandlerInterface;

/**
 * Stateful-safe Session Manager class.
 */
class SessionManager
{
    /**
     * Custom driver creators.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    /**
     * Create a new SessionManager instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * Get a session driver instance.
     */
    public function driver(?string $driver = null): SessionInterface
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return $this->buildSession($this->createDriver($driver));
    }

    /**
     * Create the session driver.
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver(string $driver): SessionHandlerInterface
    {
        if (isset($this->customCreators[$driver])) {
            return ($this->customCreators[$driver])($this->container);
        }

        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException("Session driver [{$driver}] not supported.");
    }

    /**
     * Create an instance of the "array" session driver.
     */
    protected function createArrayDriver(): SessionHandlerInterface
    {
        return new ArraySessionHandler();
    }

    /**
     * Create an instance of the "file" session driver.
     */
    protected function createFileDriver(): SessionHandlerInterface
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        $path = $config->get('session.files');

        if (!$path) {
            // Default storage path if none set in config
            $basePath = $this->container->has('app') ? $this->container->get('app')->basePath() : sys_get_temp_dir();
            $path = rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions';
        }

        $lifetime = (int) $config->get('session.lifetime', 120);

        return new FileSessionHandler($path, $lifetime);
    }

    /**
     * Build the session instance.
     */
    protected function buildSession(SessionHandlerInterface $handler): SessionInterface
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        $cookieName = (string) $config->get('session.cookie', 'embegeq_session');

        return new Store($cookieName, $handler);
    }

    /**
     * Get the default session driver name.
     */
    public function getDefaultDriver(): string
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        return (string) $config->get('session.driver', 'file');
    }

    /**
     * Register a custom driver creator closure.
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customCreators[$driver] = $callback;
    }
}
