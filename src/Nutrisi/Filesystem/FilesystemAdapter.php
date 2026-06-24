<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Filesystem;

use EmbegeQ\Nutrisi\Contracts\Filesystem\FilesystemInterface;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;

/**
 * Adapter wrapping Flysystem's FilesystemOperator.
 */
class FilesystemAdapter implements FilesystemInterface
{
    /**
     * Create a new FilesystemAdapter instance.
     */
    public function __construct(protected FilesystemOperator $driver) {}

    /**
     * Get the underlying Flysystem driver.
     */
    public function getDriver(): FilesystemOperator
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $location): bool
    {
        return $this->driver->fileExists($location);
    }

    /**
     * {@inheritdoc}
     */
    public function directoryExists(string $location): bool
    {
        return $this->driver->directoryExists($location);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $location): bool
    {
        return $this->driver->has($location);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $location): string
    {
        return $this->driver->read($location);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream(string $location)
    {
        return $this->driver->readStream($location);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents(string $location, bool $deep = false): DirectoryListing
    {
        return $this->driver->listContents($location, $deep);
    }

    /**
     * {@inheritdoc}
     */
    public function lastModified(string $path): int
    {
        return $this->driver->lastModified($path);
    }

    /**
     * {@inheritdoc}
     */
    public function fileSize(string $path): int
    {
        return $this->driver->fileSize($path);
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $path): string
    {
        return $this->driver->mimeType($path);
    }

    /**
     * {@inheritdoc}
     */
    public function visibility(string $path): string
    {
        return $this->driver->visibility($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $config
     */
    public function write(string $location, string $contents, array $config = []): void
    {
        $this->driver->write($location, $contents, $config);
    }

    /**
     * {@inheritdoc}
     *
     * @param resource $contents
     * @param array<string, mixed> $config
     */
    public function writeStream(string $location, $contents, array $config = []): void
    {
        $this->driver->writeStream($location, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $this->driver->setVisibility($path, $visibility);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $location): void
    {
        $this->driver->delete($location);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory(string $location): void
    {
        $this->driver->deleteDirectory($location);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $config
     */
    public function createDirectory(string $location, array $config = []): void
    {
        $this->driver->createDirectory($location, $config);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $config
     */
    public function move(string $source, string $destination, array $config = []): void
    {
        $this->driver->move($source, $destination, $config);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $config
     */
    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->driver->copy($source, $destination, $config);
    }
}
