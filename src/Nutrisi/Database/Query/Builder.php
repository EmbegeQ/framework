<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Query;

use Closure;
use EmbegeQ\Nutrisi\Database\Connection;
use EmbegeQ\Nutrisi\Database\Query\Grammars\Grammar;

/**
 * Fluent Query Builder for compiling and executing SQL queries.
 *
 * Provides a database-agnostic interface for selecting, inserting,
 * updating, and deleting records. Compiles parts via a driver-specific Grammar.
 */
class Builder
{
    /**
     * The columns that should be returned.
     *
     * @var array<int, string>|null
     */
    public ?array $columns = null;

    /**
     * Whether the query should return distinct results.
     */
    public bool $distinct = false;

    /**
     * The table the query is targeting.
     */
    public ?string $from = null;

    /**
     * The table joins for the query.
     *
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $joins = [];

    /**
     * The where constraints for the query.
     *
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $wheres = [];

    /**
     * The groupings for the query.
     *
     * @var array<int, string>
     */
    public array $groups = [];

    /**
     * The orderings for the query.
     *
     * @var array<int, array<string, string>>|null
     */
    public ?array $orders = [];

    /**
     * The maximum number of records to return.
     */
    public ?int $limit = null;

    /**
     * The number of records to skip.
     */
    public ?int $offset = null;

    /**
     * The aggregate function and columns.
     *
     * @var array{function: string, columns: array<int, string>}|null
     */
    public ?array $aggregate = null;

    /**
     * The query bindings.
     *
     * @var array<string, array<int, mixed>>
     */
    public array $bindings = [
        'where' => [],
    ];

    /**
     * Create a new query builder instance.
     */
    public function __construct(
        protected Connection $connection,
        protected Grammar $grammar
    ) {}

    /**
     * Set the columns to be selected.
     *
     * @param array<int, string>|string $columns
     * @return $this
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return $this
     */
    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    /**
     * Set the table which the query is targeting.
     */
    public function from(string $table, ?string $as = null): static
    {
        $this->from = $as !== null ? "{$table} as {$as}" : $table;

        return $this;
    }

    /**
     * Add a join clause to the query.
     */
    public function join(
        string $table,
        string $first,
        string $operator,
        string $second,
        string $type = 'inner'
    ): static {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * Add a left join to the query.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join to the query.
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string|Closure $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function where(
        string|Closure $column,
        mixed $operator = null,
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        if ($column instanceof Closure) {
            throw new \InvalidArgumentException('Nested where closures are not implemented.');
        }

        // If two parameters are passed, we assume the operator is '='.
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        if ($value === null && $operator === '=') {
            $this->whereNull($column, $boolean);

            return $this;
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'boolean' => $boolean,
        ];

        $this->addBinding($value, 'where');

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param string|Closure $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
        ];

        foreach ($values as $value) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'and'): static
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Add a "where null" clause to the query.
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => $not ? 'not_null' : 'null',
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     */
    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'or');
    }

    /**
     * Add a "where not null" clause to the query.
     */
    public function whereNotNull(string $column, string $boolean = 'and'): static
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add an "or where not null" clause to the query.
     */
    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where between" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException('Between values must contain exactly 2 elements.');
        }

        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not,
        ];

        $this->addBinding($values[0], 'where');
        $this->addBinding($values[1], 'where');

        return $this;
    }

    /**
     * Add an "or where between" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Add a "where not between" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function whereNotBetween(string $column, array $values, string $boolean = 'and'): static
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not between" clause to the query.
     *
     * @param array<int, mixed> $values
     */
    public function orWhereNotBetween(string $column, array $values): static
    {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param array<int, mixed> $bindings
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean,
        ];

        foreach ($bindings as $value) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add an "or where raw" clause to the query.
     *
     * @param array<int, mixed> $bindings
     */
    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param array<int, string>|string ...$groups
     */
    public function groupBy(...$groups): static
    {
        $first = $groups[0] ?? null;
        $actualGroups = is_array($first) ? $first : $groups;

        foreach ($actualGroups as $group) {
            if (is_string($group)) {
                $this->groups[] = $group;
            }
        }

        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
        ];

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an ascending "order by" clause to the query.
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * Set the "limit" value of the query.
     */
    public function limit(int $value): static
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     */
    public function take(int $value): static
    {
        return $this->limit($value);
    }

    /**
     * Set the "offset" value of the query.
     */
    public function offset(int $value): static
    {
        if ($value >= 0) {
            $this->offset = $value;
        }

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     */
    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    /**
     * Add a binding to the query.
     */
    public function addBinding(mixed $value, string $type = 'where'): static
    {
        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Get the query bindings in a flat array.
     *
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings['where'];
    }

    /**
     * Get the SQL representation of the query.
     */
    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array<int, string>|string $columns
     * @return array<array<string, mixed>>
     */
    public function get(array|string $columns = ['*']): array
    {
        if ($columns !== ['*']) {
            $this->columns = is_array($columns) ? $columns : func_get_args();
        }

        return $this->connection->select(
            $this->toSql(),
            $this->getBindings()
        );
    }

    /**
     * Execute the query and get the first result.
     *
     * @param array<int, string>|string $columns
     * @return array<string, mixed>|null
     */
    public function first(array|string $columns = ['*']): ?array
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * Get a single column's value from the first result of a query.
     */
    public function value(string $column): mixed
    {
        $result = $this->first([$column]);

        return $result !== null ? ($result[$column] ?? null) : null;
    }

    /**
     * Pluck an array of values from a single column.
     *
     * @return array<mixed, mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->get($key === null ? [$column] : [$column, $key]);

        $pluck = [];

        foreach ($results as $row) {
            if ($key !== null) {
                $pluck[$row[$key]] = $row[$column];
            } else {
                $pluck[] = $row[$column];
            }
        }

        return $pluck;
    }

    /**
     * Retrieve the "count" result of the query.
     */
    public function count(string $columns = '*'): int
    {
        return (int) $this->aggregate('count', [$columns]);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param array<int, string> $columns
     */
    public function aggregate(string $function, array $columns = ['*']): mixed
    {
        $this->aggregate = compact('function', 'columns');

        $previousColumns = $this->columns;
        $previousLimit = $this->limit;
        $previousOffset = $this->offset;

        $this->limit = null;
        $this->offset = null;

        $results = $this->connection->select(
            $this->toSql(),
            $this->getBindings()
        );

        $this->aggregate = null;
        $this->columns = $previousColumns;
        $this->limit = $previousLimit;
        $this->offset = $previousOffset;

        if (count($results) > 0) {
            return array_values($results[0])[0];
        }

        return null;
    }

    /**
     * Determine if any rows exist for the current query.
     */
    public function exists(): bool
    {
        $results = $this->connection->select(
            $this->grammar->compileExists($this),
            $this->getBindings()
        );

        if (count($results) > 0) {
            return (bool) array_values($results[0])[0];
        }

        return false;
    }

    /**
     * Insert a new record into the database.
     *
     * @param array<string, mixed>|array<int, array<string, mixed>> $values
     */
    public function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            /** @var array<string, mixed> $values */
            return $this->connection->insert(
                $this->grammar->compileInsert($this, $values),
                array_values($values)
            );
        }

        /** @var array<int, array<string, mixed>> $values */
        $bindings = [];
        foreach ($values as $record) {
            foreach ($record as $value) {
                $bindings[] = $value;
            }
        }

        return $this->connection->insert(
            $this->grammar->compileInsertBatch($this, $values),
            $bindings
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param array<string, mixed> $values
     */
    public function insertGetId(array $values, ?string $sequence = null): int
    {
        $this->connection->insert(
            $this->grammar->compileInsert($this, $values),
            array_values($values)
        );

        $id = $this->connection->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : 0;
    }

    /**
     * Update a record in the database.
     *
     * @param array<string, mixed> $values
     */
    public function update(array $values): int
    {
        $sql = $this->grammar->compileUpdate($this, $values);

        $bindings = array_merge(array_values($values), $this->getBindings());

        return $this->connection->update($sql, $bindings);
    }

    /**
     * Delete a record from the database.
     */
    public function delete(): int
    {
        $sql = $this->grammar->compileDelete($this);

        return $this->connection->delete($sql, $this->getBindings());
    }
}
