<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Query\Grammars;

/**
 * PostgreSQL-specific query grammar.
 *
 * PostgreSQL uses double-quotes for identifier quoting (same as ANSI SQL),
 * so this grammar inherits the base behavior.
 */
class PostgresGrammar extends Grammar
{
    // PostgreSQL uses double-quoted identifiers by default, which is the
    // same behavior as the base Grammar class.
}
