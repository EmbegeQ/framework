<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Cache;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache Manager implementing PSR-16 CacheInterface.
 */
class CacheManager implements CacheInterface
{
    /**
     * The resolved cache stores.
     *
     * @var array<string, CacheInterface>
     */
    protected array $stores = [];

    /**
     * Custom store creators.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    /**
     * Create a new CacheManager instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * Get a cache store instance by name.
     */
    public function store(?string $name = null): CacheInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] ??= $this->resolve($name);
    }

    /**
     * Get a cache driver instance by name.
     */
    public function driver(?string $driver = null): CacheInterface
    {
        return $this->store($driver);
    }

    /**
     * Resolve the given store.
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): CacheInterface
    {
        if (isset($this->customCreators[$name])) {
            return ($this->customCreators[$name])($this->container);
        }

        $method = 'create' . ucfirst($name) . 'Store';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException("Cache store [{$name}] not supported.");
    }

    /**
     * Create an instance of the "array" cache store.
     */
    protected function createArrayStore(): CacheInterface
    {
        return new ArrayCacheStore();
    }

    /**
     * Create an instance of the "file" cache store.
     */
    protected function createFileStore(): CacheInterface
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        $directory = $config->get('cache.stores.file.path');

        if (!$directory) {
            $basePath = $this->container->has('app') ? $this->container->get('app')->basePath() : sys_get_temp_dir();
            $directory = rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data';
        }

        return new FileCacheStore($directory);
    }

    /**
     * Get the default cache driver name.
     */
    public function getDefaultDriver(): string
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        return (string) $config->get('cache.default', 'file');
    }

    /**
     * Register a custom store creator closure.
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customCreators[$driver] = $callback;
    }

    // =========================================================================
    // PSR-16: CacheInterface Delegation
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->store()->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->store()->clear();
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string> $keys
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->store()->getMultiple($keys, $default);
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->store()->deleteMultiple($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }
}
