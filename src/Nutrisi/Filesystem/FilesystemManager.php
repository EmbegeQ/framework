<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Filesystem;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Filesystem\FilesystemInterface;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Filesystem Manager class for resolving storage disks.
 */
class FilesystemManager
{
    /**
     * The resolved filesystem disks.
     *
     * @var array<string, FilesystemInterface>
     */
    protected array $disks = [];

    /**
     * Custom disk creators.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    /**
     * Create a new FilesystemManager instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * Get a filesystem disk instance.
     */
    public function disk(?string $name = null): FilesystemInterface
    {
        $name = $name ?: $this->getDefaultCloudDriver();

        return $this->disks[$name] ??= $this->resolve($name);
    }

    /**
     * Get a filesystem disk instance.
     */
    public function drive(?string $name = null): FilesystemInterface
    {
        return $this->disk($name);
    }

    /**
     * Resolve the disk instance.
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): FilesystemInterface
    {
        if (isset($this->customCreators[$name])) {
            return ($this->customCreators[$name])($this->container);
        }

        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');
        $diskConfig = $config->get("filesystems.disks.{$name}");

        if (!$diskConfig) {
            if ($name === 'local') {
                $diskConfig = ['driver' => 'local'];
            } else {
                throw new \InvalidArgumentException("Filesystem disk [{$name}] is not configured.");
            }
        }

        $driver = $diskConfig['driver'] ?? 'local';
        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($diskConfig);
        }

        throw new \InvalidArgumentException("Filesystem driver [{$driver}] not supported.");
    }

    /**
     * Create an instance of the "local" filesystem driver.
     *
     * @param array<string, mixed> $config
     */
    protected function createLocalDriver(array $config): FilesystemInterface
    {
        $root = $config['root'] ?? null;
        if (!$root) {
            $basePath = $this->container->has('app') ? $this->container->get('app')->basePath() : sys_get_temp_dir();
            $root = rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app';
        }

        $links = $config['links'] ?? LocalFilesystemAdapter::SKIP_LINKS;

        $adapter = new LocalFilesystemAdapter($root, null, LOCK_EX, $links);
        $flysystem = new Flysystem($adapter, $config);

        return new FilesystemAdapter($flysystem);
    }

    /**
     * Get the default filesystem disk name.
     */
    public function getDefaultCloudDriver(): string
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        return (string) $config->get('filesystems.default', 'local');
    }

    /**
     * Register a custom driver creator closure.
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customCreators[$driver] = $callback;
    }
}
