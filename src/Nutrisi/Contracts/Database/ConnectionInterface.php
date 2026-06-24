<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Database;

use PDO;
use Closure;

/**
 * Interface ConnectionInterface
 *
 * @package EmbegeQ\Nutrisi\Contracts\Database
 */
interface ConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
     *
     * @param string $table
     * @param string|null $as
     * @return mixed
     */
    public function table(string $table, ?string $as = null): mixed;

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return array<array<string, mixed>>
     */
    public function select(string $query, array $bindings = []): array;

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return mixed
     */
    public function selectOne(string $query, array $bindings = []): mixed;

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return bool
     */
    public function insert(string $query, array $bindings = []): bool;

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return int
     */
    public function update(string $query, array $bindings = []): int;

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return int
     */
    public function delete(string $query, array $bindings = []): int;

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param string $query
     * @return bool
     */
    public function unprepared(string $query): bool;

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return int
     */
    public function affectingStatement(string $query, array $bindings = []): int;

    /**
     * Get the underlying PDO connection.
     *
     * @return PDO
     */
    public function getPdo(): PDO;

    /**
     * Execute a Closure within a transaction.
     *
     * @template T
     * @param Closure(self): T $callback
     * @return T
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback): mixed;

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack(): void;

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName(): string;
}
