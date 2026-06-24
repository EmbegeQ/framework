<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * File-based PSR-16 cache implementation.
 */
class FileCacheStore implements CacheInterface
{
    /**
     * Create a new FileCacheStore instance.
     *
     * @param  string  $directory  The root directory for cache files.
     */
    public function __construct(protected string $directory) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        $expire = (int) substr($content, 0, 10);
        if (time() >= $expire) {
            $this->delete($key);
            return $default;
        }

        $serialized = substr($content, 10);
        return @unserialize($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $expire = $this->getExpirationTimestamp($ttl);
        $content = $expire . serialize($value);

        return @file_put_contents($file, $content, LOCK_EX) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->emptyDirectory($this->directory);
        return true;
    }

    /**
     * Recursively delete all files and directories within the directory.
     */
    protected function emptyDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->emptyDirectory($filePath);
                @rmdir($filePath);
            } else {
                @unlink($filePath);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string> $keys
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get((string) $key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return false;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return false;
        }

        $expire = (int) substr($content, 0, 10);
        if (time() >= $expire) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Get the absolute file path for a cache key.
     */
    protected function getFilePath(string $key): string
    {
        $hash = sha1($key);
        $parts = [
            substr($hash, 0, 2),
            substr($hash, 2, 2)
        ];

        return rtrim($this->directory, '\/') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $hash;
    }

    /**
     * Get the expiration timestamp from a TTL value.
     */
    protected function getExpirationTimestamp(null|int|\DateInterval $ttl): int
    {
        if ($ttl === null) {
            return time() + 315360000; // ~10 years
        }

        if ($ttl instanceof \DateInterval) {
            return (new \DateTime())->add($ttl)->getTimestamp();
        }

        return time() + $ttl;
    }
}
