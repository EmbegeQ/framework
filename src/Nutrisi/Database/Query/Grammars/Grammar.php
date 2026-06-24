<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Query\Grammars;

use EmbegeQ\Nutrisi\Database\Connection;
use EmbegeQ\Nutrisi\Database\Query\Builder;

/**
 * Base SQL Grammar for compiling query builder components into SQL strings.
 *
 * Each database driver can extend this class to override compilation methods
 * for driver-specific syntax (identifier quoting, limit/offset, etc.).
 */
class Grammar
{
    /**
     * The components that make up a select clause, in compilation order.
     *
     * @var array<int, string>
     */
    protected array $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * Create a new grammar instance.
     */
    public function __construct(protected Connection $connection) {}

    /**
     * Compile a select query into SQL.
     */
    public function compileSelect(Builder $query): string
    {
        if ($query->aggregate !== null) {
            return $this->compileAggregate($query);
        }

        $columns = $query->columns;

        if ($columns === null || $columns === []) {
            $query->columns = ['*'];
        }

        $sql = trim($this->concatenate(
            $this->compileComponents($query)
        ));

        $query->columns = $columns;

        return $sql;
    }

    /**
     * Compile the components of the select query.
     *
     * @return array<string, string>
     */
    protected function compileComponents(Builder $query): array
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            $method = 'compile' . ucfirst($component);

            if (method_exists($this, $method)) {
                $compiled = $this->{$method}($query);

                if ($compiled !== '' && $compiled !== null) {
                    $sql[$component] = $compiled;
                }
            }
        }

        return $sql;
    }

    /**
     * Compile an aggregate select clause.
     */
    protected function compileAggregate(Builder $query): string
    {
        if ($query->aggregate === null) {
            return '';
        }

        $function = $query->aggregate['function'];

        /** @var array<int, string> $aggregateColumns */
        $aggregateColumns = $query->aggregate['columns'];

        $column = $this->columnize($aggregateColumns);

        if ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        $sql = 'select ' . $function . '(' . $column . ') as ' . $this->wrap('aggregate');

        if ($query->from !== null) {
            $sql .= ' ' . $this->compileFrom($query);
        }

        $wheres = $this->compileWheres($query);
        if ($wheres !== '') {
            $sql .= ' ' . $wheres;
        }

        return $sql;
    }

    /**
     * Compile the "select *" portion of the query.
     */
    protected function compileColumns(Builder $query): string
    {
        if ($query->aggregate !== null) {
            return '';
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        /** @var array<int, string> $columns */
        $columns = $query->columns ?? ['*'];

        return $select . $this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     */
    protected function compileFrom(Builder $query): string
    {
        if ($query->from === null) {
            return '';
        }

        return 'from ' . $this->wrapTable($query->from);
    }

    /**
     * Compile the "join" portions of the query.
     */
    protected function compileJoins(Builder $query): string
    {
        if ($query->joins === null || $query->joins === []) {
            return '';
        }

        $parts = [];

        foreach ($query->joins as $join) {
            $table = $this->wrapTable($join['table']);
            $type = strtolower($join['type']);

            $clause = $this->wrap($join['first']) . ' ' . $join['operator'] . ' ' . $this->wrap($join['second']);

            $parts[] = "{$type} join {$table} on {$clause}";
        }

        return implode(' ', $parts);
    }

    /**
     * Compile the "where" portions of the query.
     */
    public function compileWheres(Builder $query): string
    {
        if ($query->wheres === null || $query->wheres === []) {
            return '';
        }

        $parts = [];

        foreach ($query->wheres as $index => $where) {
            $compiled = $this->compileWhere($where);

            if ($index === 0) {
                $parts[] = $compiled;
            } else {
                $parts[] = $where['boolean'] . ' ' . $compiled;
            }
        }

        return 'where ' . implode(' ', $parts);
    }

    /**
     * Compile a single where clause.
     *
     * @param array<string, mixed> $where
     */
    protected function compileWhere(array $where): string
    {
        $type = $where['type'] ?? 'basic';

        return match ($type) {
            'basic' => $this->compileWhereBasic($where),
            'in' => $this->compileWhereIn($where),
            'null' => $this->compileWhereNull($where),
            'not_null' => $this->compileWhereNotNull($where),
            'between' => $this->compileWhereBetween($where),
            'raw' => (string) $where['sql'],
            default => $this->compileWhereBasic($where),
        };
    }

    /**
     * Compile a basic where clause.
     *
     * @param array<string, mixed> $where
     */
    protected function compileWhereBasic(array $where): string
    {
        return $this->wrap((string) $where['column']) . ' ' . $where['operator'] . ' ?';
    }

    /**
     * Compile a "where in" clause.
     *
     * @param array<string, mixed> $where
     */
    protected function compileWhereIn(array $where): string
    {
        /** @var array<int, mixed> $values */
        $values = $where['values'];

        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $not = !empty($where['not']) ? 'not ' : '';

        return $this->wrap((string) $where['column']) . ' ' . $not . 'in (' . $placeholders . ')';
    }

    /**
     * Compile a "where null" clause.
     *
     * @param array<string, mixed> $where
     */
    protected function compileWhereNull(array $where): string
    {
        return $this->wrap((string) $where['column']) . ' is null';
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param array<string, mixed> $where
     */
    protected function compileWhereNotNull(array $where): string
    {
        return $this->wrap((string) $where['column']) . ' is not null';
    }

    /**
     * Compile a "where between" clause.
     *
     * @param array<string, mixed> $where
     */
    protected function compileWhereBetween(array $where): string
    {
        $not = !empty($where['not']) ? 'not ' : '';

        return $this->wrap((string) $where['column']) . ' ' . $not . 'between ? and ?';
    }

    /**
     * Compile the "group by" portions of the query.
     */
    protected function compileGroups(Builder $query): string
    {
        if ($query->groups === []) {
            return '';
        }

        return 'group by ' . $this->columnize($query->groups);
    }

    /**
     * Compile the "order by" portions of the query.
     */
    protected function compileOrders(Builder $query): string
    {
        if ($query->orders === null || $query->orders === []) {
            return '';
        }

        $parts = [];

        foreach ($query->orders as $order) {
            $parts[] = $this->wrap($order['column']) . ' ' . $order['direction'];
        }

        return 'order by ' . implode(', ', $parts);
    }

    /**
     * Compile the "limit" portions of the query.
     */
    protected function compileLimit(Builder $query): string
    {
        if ($query->limit === null) {
            return '';
        }

        return 'limit ' . $query->limit;
    }

    /**
     * Compile the "offset" portions of the query.
     */
    protected function compileOffset(Builder $query): string
    {
        if ($query->offset === null) {
            return '';
        }

        return 'offset ' . $query->offset;
    }

    /**
     * Compile an insert statement.
     *
     * @param array<string, mixed> $values
     */
    public function compileInsert(Builder $query, array $values): string
    {
        $table = $this->wrapTable($query->from ?? '');

        $columns = array_keys($values);
        $columnsString = $this->columnize($columns);

        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        return "insert into {$table} ({$columnsString}) values ({$placeholders})";
    }

    /**
     * Compile a batch insert statement.
     *
     * @param array<int, array<string, mixed>> $records
     */
    public function compileInsertBatch(Builder $query, array $records): string
    {
        $table = $this->wrapTable($query->from ?? '');

        if ($records === []) {
            return "insert into {$table} default values";
        }

        $columns = array_keys($records[0]);
        $columnsString = $this->columnize($columns);

        $rowPlaceholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($records), $rowPlaceholders));

        return "insert into {$table} ({$columnsString}) values {$allPlaceholders}";
    }

    /**
     * Compile an update statement.
     *
     * @param array<string, mixed> $values
     */
    public function compileUpdate(Builder $query, array $values): string
    {
        $table = $this->wrapTable($query->from ?? '');

        $columns = [];
        foreach (array_keys($values) as $key) {
            $columns[] = $this->wrap($key) . ' = ?';
        }

        $columnsString = implode(', ', $columns);
        $sql = "update {$table} set {$columnsString}";

        $wheres = $this->compileWheres($query);

        if ($wheres !== '') {
            $sql .= ' ' . $wheres;
        }

        return $sql;
    }

    /**
     * Compile a delete statement.
     */
    public function compileDelete(Builder $query): string
    {
        $table = $this->wrapTable($query->from ?? '');
        $sql = "delete from {$table}";

        $wheres = $this->compileWheres($query);

        if ($wheres !== '') {
            $sql .= ' ' . $wheres;
        }

        return $sql;
    }

    /**
     * Compile an exists statement.
     */
    public function compileExists(Builder $query): string
    {
        $select = $this->compileSelect($query);

        return "select exists({$select}) as " . $this->wrap('exists');
    }

    /**
     * Wrap a value in keyword identifiers.
     */
    public function wrap(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        // Handle aliased values.
        if (stripos($value, ' as ') !== false) {
            $segments = preg_split('/\s+as\s+/i', $value, 2);

            if ($segments !== false && count($segments) === 2) {
                return $this->wrap($segments[0]) . ' as ' . $this->wrapValue($segments[1]);
            }
        }

        // Handle dot-separated identifiers (table.column).
        if (str_contains($value, '.')) {
            $parts = explode('.', $value);

            $parts[0] = $this->wrapTable($parts[0]);

            for ($i = 1; $i < count($parts); $i++) {
                $parts[$i] = $this->wrapValue($parts[$i]);
            }

            return implode('.', $parts);
        }

        return $this->wrapValue($value);
    }

    /**
     * Wrap a single value in keyword identifiers.
     * Override this in driver-specific grammars (e.g., backticks for MySQL).
     */
    protected function wrapValue(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Wrap a table name in keyword identifiers.
     */
    public function wrapTable(string $table): string
    {
        $prefix = $this->connection->getTablePrefix();

        return $this->wrap($prefix . $table);
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array<int, string> $columns
     */
    public function columnize(array $columns): string
    {
        return implode(', ', array_map(fn (string $col): string => $this->wrap($col), $columns));
    }

    /**
     * Create query parameter placeholders for an array.
     *
     * @param array<int, mixed> $values
     */
    public function parameterize(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param array<string, string> $segments
     */
    protected function concatenate(array $segments): string
    {
        return implode(' ', array_filter($segments, fn (string $value): bool => $value !== ''));
    }
}
