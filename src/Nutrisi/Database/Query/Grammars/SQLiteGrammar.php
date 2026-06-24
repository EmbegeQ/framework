<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Query\Grammars;

/**
 * SQLite-specific query grammar.
 *
 * SQLite uses double-quotes for identifier quoting (same as ANSI SQL),
 * so this grammar mostly inherits the base behavior.
 */
class SQLiteGrammar extends Grammar
{
    // SQLite uses double-quoted identifiers by default, which is the
    // same behavior as the base Grammar class. No overrides are needed
    // for the current feature set (select, insert, update, delete).
}
