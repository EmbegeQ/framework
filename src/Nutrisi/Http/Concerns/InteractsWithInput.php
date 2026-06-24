<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Concerns;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interacts With Input.
 *
 * Provides fluent methods for accessing request input (query, body, files).
 *
 * @mixin \Psr\Http\Message\ServerRequestInterface
 */
trait InteractsWithInput
{
    /**
     * Get a request input value by key.
     *
     * @param string $key Dot notation supported (e.g. 'user.name')
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return $this->dotGet($all, $key, $default);
    }

    /**
     * Get all request input (query + parsed body).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $data = [];

        // Merge query parameters
        if ($this instanceof ServerRequestInterface) {
            $data = array_merge($data, $this->getQueryParams());

            // Merge parsed body (POST, PUT, PATCH, etc.)
            $body = $this->getParsedBody();
            if (is_array($body)) {
                $data = array_merge($data, $body);
            }
        }

        return $data;
    }

    /**
     * Get only specific input keys.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Get all input except specific keys.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        return array_diff_key($all, array_flip($keys));
    }

    /**
     * Check if input key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Check if any of the given keys exist.
     *
     * @param array<int, string> $keys
     * @return bool
     */
    public function hasAny(array $keys): bool
    {
        $all = $this->all();
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get query string parameters.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($this instanceof ServerRequestInterface) {
            $query = $this->getQueryParams();
            if ($key === null) {
                return $query;
            }
            return $this->dotGet($query, $key, $default);
        }
        return $default;
    }

    /**
     * Get uploaded files.
     *
     * @param string|null $key
     * @return mixed
     */
    public function file(string $key = null): mixed
    {
        if ($this instanceof ServerRequestInterface) {
            $files = $this->getUploadedFiles();
            if ($key === null) {
                return $files;
            }
            return $files[$key] ?? null;
        }
        return null;
    }

    /**
     * Get a nested value using dot notation.
     *
     * @param array<string, mixed> $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function dotGet(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        // Handle dot notation
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
