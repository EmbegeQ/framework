<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Session;

/**
 * Session store contract for EmbegeQ.
 */
interface SessionInterface
{
    /**
     * Get the name of the session.
     */
    public function getName(): string;

    /**
     * Set the name of the session.
     */
    public function setName(string $name): void;

    /**
     * Get the current session ID.
     */
    public function getId(): string;

    /**
     * Set the session ID.
     */
    public function setId(string $id): void;

    /**
     * Start the session, reading the data from the handler.
     */
    public function start(): bool;

    /**
     * Save the session data to storage.
     */
    public function save(): void;

    /**
     * Get all of the session data.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Checks if a key exists in the session.
     */
    public function has(string $key): bool;

    /**
     * Get an item from the session.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Put a key / value pair in the session.
     */
    public function put(string $key, mixed $value): void;

    /**
     * Remove an item from the session.
     */
    public function forget(string $key): void;

    /**
     * Remove all items from the session.
     */
    public function flush(): void;

    /**
     * Flush the session data and regenerate the ID.
     */
    public function invalidate(): bool;

    /**
     * Generate a new session identifier.
     */
    public function regenerate(bool $destroy = false): bool;

    /**
     * Get the CSRF token value.
     */
    public function token(): string;

    /**
     * Regenerate the CSRF token value.
     */
    public function regenerateToken(): void;
}
