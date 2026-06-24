<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database;

use Closure;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionInterface;
use EmbegeQ\Nutrisi\Database\Query\Builder;
use EmbegeQ\Nutrisi\Database\Query\Grammars\Grammar;
use PDO;
use PDOStatement;

/**
 * Database Connection class wrapping a PDO instance.
 *
 * Provides a clean, memory-safe API for executing queries, managing
 * transactions (including nested savepoints), and spawning query builders.
 * Designed for long-running stateful workers (FrankenPHP, RoadRunner).
 *
 * IMPORTANT: This class does NOT use static state. Each instance is
 * bound to its own PDO handle and grammar, ensuring isolation.
 */
class Connection implements ConnectionInterface
{
    /**
     * The active PDO connection (or a lazy-resolving Closure).
     *
     * @var PDO|(Closure(): PDO)
     */
    protected PDO|Closure $pdo;

    /**
     * The name of the connected database.
     */
    protected string $database;

    /**
     * The table prefix for the connection.
     */
    protected string $tablePrefix;

    /**
     * The database connection configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * The query grammar implementation.
     */
    protected Grammar $queryGrammar;

    /**
     * The current transaction depth (for savepoint nesting).
     */
    protected int $transactionLevel = 0;

    /**
     * The query log for debugging.
     *
     * @var array<int, array{query: string, bindings: array<string|int, mixed>, time: float}>
     */
    protected array $queryLog = [];

    /**
     * Whether query logging is active.
     */
    protected bool $loggingQueries = false;

    /**
     * Create a new database connection instance.
     *
     * @param PDO|(Closure(): PDO) $pdo
     * @param string $database
     * @param string $tablePrefix
     * @param array<string, mixed> $config
     */
    public function __construct(
        PDO|Closure $pdo,
        string $database = '',
        string $tablePrefix = '',
        array $config = [],
    ) {
        $this->pdo = $pdo;
        $this->database = $database;
        $this->tablePrefix = $tablePrefix;
        $this->config = $config;

        $this->useDefaultQueryGrammar();
    }

    /**
     * Set the query grammar to the default implementation.
     */
    public function useDefaultQueryGrammar(): void
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     */
    protected function getDefaultQueryGrammar(): Grammar
    {
        return new Grammar($this);
    }

    /**
     * Begin a fluent query against a database table.
     */
    public function table(string $table, ?string $as = null): mixed
    {
        return $this->query()->from($table, $as);
    }

    /**
     * Get a new query builder instance.
     */
    public function query(): Builder
    {
        return new Builder($this, $this->getQueryGrammar());
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return mixed
     */
    public function selectOne(string $query, array $bindings = []): mixed
    {
        $records = $this->select($query, $bindings);

        return array_shift($records);
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return array<array<string, mixed>>
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function (string $query, array $bindings): array {
            $statement = $this->getPdo()->prepare($query);
            $this->bindValues($statement, $bindings);
            $statement->execute();

            /** @var array<array<string, mixed>> $result */
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

            return $result;
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return bool
     */
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function (string $query, array $bindings): bool {
            $statement = $this->getPdo()->prepare($query);
            $this->bindValues($statement, $bindings);

            return $statement->execute();
        });
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return int
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return int
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @return int
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function (string $query, array $bindings): int {
            $statement = $this->getPdo()->prepare($query);
            $this->bindValues($statement, $bindings);
            $statement->execute();

            return $statement->rowCount();
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     */
    public function unprepared(string $query): bool
    {
        return $this->run($query, [], function (string $query): bool {
            return $this->getPdo()->exec($query) !== false;
        });
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @template T
     * @param Closure(self): T $callback
     * @return T
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Start a new database transaction (supports nested savepoints).
     */
    public function beginTransaction(): void
    {
        if ($this->transactionLevel === 0) {
            $this->getPdo()->beginTransaction();
        } else {
            $this->getPdo()->exec(
                'SAVEPOINT trans_' . $this->transactionLevel
            );
        }

        $this->transactionLevel++;
    }

    /**
     * Commit the active database transaction.
     */
    public function commit(): void
    {
        if ($this->transactionLevel <= 0) {
            return;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->getPdo()->commit();
        } else {
            $this->getPdo()->exec(
                'RELEASE SAVEPOINT trans_' . $this->transactionLevel
            );
        }
    }

    /**
     * Rollback the active database transaction.
     */
    public function rollBack(): void
    {
        if ($this->transactionLevel <= 0) {
            return;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->getPdo()->rollBack();
        } else {
            $this->getPdo()->exec(
                'ROLLBACK TO SAVEPOINT trans_' . $this->transactionLevel
            );
        }
    }

    /**
     * Get the name of the connected database.
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * Get the table prefix.
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Get the underlying PDO connection, resolving it lazily if needed.
     */
    public function getPdo(): PDO
    {
        if ($this->pdo instanceof Closure) {
            $this->pdo = ($this->pdo)();
        }

        $pdo = $this->pdo;

        if ($pdo instanceof Closure) {
            throw new \RuntimeException('Failed to resolve PDO instance.');
        }

        return $pdo;
    }

    /**
     * Set the PDO connection.
     *
     * @param PDO|(Closure(): PDO) $pdo
     * @return $this
     */
    public function setPdo(PDO|Closure $pdo): static
    {
        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Get the query grammar used by the connection.
     */
    public function getQueryGrammar(): Grammar
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     */
    public function setQueryGrammar(Grammar $grammar): static
    {
        $this->queryGrammar = $grammar;

        return $this;
    }

    /**
     * Get the connection configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the transaction nesting level.
     */
    public function transactionLevel(): int
    {
        return $this->transactionLevel;
    }

    /**
     * Enable the query log on the connection.
     */
    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     */
    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    /**
     * Get the connection query log.
     *
     * @return array<int, array{query: string, bindings: array<string|int, mixed>, time: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Get the last insert ID from the connection.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->getPdo()->lastInsertId($name);
    }

    /**
     * Disconnect from the underlying PDO connection.
     */
    public function disconnect(): void
    {
        $this->pdo = static function (): never {
            throw new \RuntimeException('PDO connection has been disconnected.');
        };

        $this->transactionLevel = 0;
    }

    /**
     * Run a SQL statement, measuring execution time and optionally logging it.
     *
     * @template TReturn
     * @param string $query
     * @param array<string|int, mixed> $bindings
     * @param Closure(string, array<string|int, mixed>): TReturn $callback
     * @return TReturn
     */
    protected function run(string $query, array $bindings, Closure $callback): mixed
    {
        $start = microtime(true);

        $result = $callback($query, $bindings);

        $time = round((microtime(true) - $start) * 1000, 2);

        if ($this->loggingQueries) {
            $this->queryLog[] = ['query' => $query, 'bindings' => $bindings, 'time' => $time];
        }

        return $result;
    }

    /**
     * Bind values to a PDO statement.
     *
     * @param PDOStatement $statement
     * @param array<string|int, mixed> $bindings
     */
    protected function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach (array_values($bindings) as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue($key + 1, $value, $type);
        }
    }
}
