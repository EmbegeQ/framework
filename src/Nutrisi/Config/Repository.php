<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Config;

use ArrayAccess;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;

/**
 * Configuration Repository with dot-notation access.
 *
 * Stores all application configuration as a nested array and provides
 * fluent access via dot-delimited keys (e.g., 'database.connections.mysql.host').
 *
 * This is registered as a singleton in the ApplicationContainer and persists
 * for the lifetime of the worker process.
 *
 * @implements ArrayAccess<string, mixed>
 */
class Repository implements RepositoryInterface, ArrayAccess
{
    /**
     * All of the configuration items.
     *
     * @var array<string, mixed>
     */
    private array $items;

    /**
     * Create a new configuration repository.
     *
     * @param  array<string, mixed>  $items  Initial configuration items.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->getNestedValue($this->items, $key) !== $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        $value = $this->getNestedValue($this->items, $key);

        return $value === $this ? $default : $value;
    }

    /**
     * Get multiple configuration values.
     *
     * @param  array<string, mixed>  $keys  An array of key => default pairs.
     * @return array<string, mixed>
     */
    private function getMany(array $keys): array
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                $key = (string) $default;
                $default = null;
            }

            $config[$key] = $this->get($key, $default);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string|array $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            $this->setNestedValue($this->items, $k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(string $key, mixed $value): void
    {
        $array = (array) $this->get($key, []);
        array_unshift($array, $value);
        $this->set($key, $array);
    }

    /**
     * {@inheritdoc}
     */
    public function push(string $key, mixed $value): void
    {
        $array = (array) $this->get($key, []);
        $array[] = $value;
        $this->set($key, $array);
    }

    // =========================================================================
    // Dot-Notation Helpers
    // =========================================================================

    /**
     * Get a value from a nested array using dot notation.
     *
     * Returns `$this` as a sentinel value when the key is not found
     * (since the actual value could legitimately be `null`).
     *
     * @param  array<string, mixed>  $array  The array to traverse.
     * @param  string  $key  The dot-delimited key.
     * @return mixed  The found value, or `$this` if not found.
     */
    private function getNestedValue(array $array, string $key): mixed
    {
        $segments = explode('.', $key);

        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $this; // Sentinel: key not found.
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Set a value in a nested array using dot notation.
     *
     * @param  array<string, mixed>  $array  The array to modify (by reference).
     * @param  string  $key  The dot-delimited key.
     * @param  mixed  $value  The value to set.
     * @return void
     */
    private function setNestedValue(array &$array, string $key, mixed $value): void
    {
        $segments = explode('.', $key);

        $current = &$array;

        foreach ($segments as $i => $segment) {
            // If this is the last segment, set the value.
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;

                return;
            }

            // Ensure intermediate segments are arrays.
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    // =========================================================================
    // ArrayAccess Implementation
    // =========================================================================

    /**
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    /**
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    /**
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    /**
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->set((string) $offset, null);
    }
}
