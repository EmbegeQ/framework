<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Config;

/**
 * Configuration Repository contract for the EmbegeQ framework.
 *
 * Provides a unified interface for accessing application configuration values
 * with support for dot-notation key traversal (e.g., 'database.connections.mysql.host').
 */
interface RepositoryInterface
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key  The configuration key (supports dot notation).
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get the specified configuration value.
     *
     * @param  string|array<string, mixed>  $key  A single key (dot notation) or an array of key-default pairs.
     * @param  mixed  $default  The default value if the key does not exist.
     * @return mixed
     */
    public function get(string|array $key, mixed $default = null): mixed;

    /**
     * Set a given configuration value.
     *
     * @param  string|array<string, mixed>  $key  A single key (dot notation) or an array of key-value pairs.
     * @param  mixed  $value  The value to set (ignored when $key is an array).
     * @return void
     */
    public function set(string|array $key, mixed $value = null): void;

    /**
     * Get all of the configuration items for the application.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $value  The value to prepend.
     * @return void
     */
    public function prepend(string $key, mixed $value): void;

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $value  The value to push.
     * @return void
     */
    public function push(string $key, mixed $value): void;
}
