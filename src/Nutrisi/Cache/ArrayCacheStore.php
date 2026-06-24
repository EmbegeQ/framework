<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * In-memory PSR-16 cache implementation.
 */
class ArrayCacheStore implements CacheInterface
{
    /**
     * The array storing cached items.
     *
     * @var array<string, array{value: mixed, expires: int}>
     */
    protected array $storage = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->storage[$key]['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'expires' => $this->getExpirationTimestamp($ttl),
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->storage = [];
        return true;
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
        if (!isset($this->storage[$key])) {
            return false;
        }

        if (time() >= $this->storage[$key]['expires']) {
            unset($this->storage[$key]);
            return false;
        }

        return true;
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
