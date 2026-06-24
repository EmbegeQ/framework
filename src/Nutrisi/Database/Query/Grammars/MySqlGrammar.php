<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Query\Grammars;

/**
 * MySQL-specific query grammar.
 *
 * MySQL uses backtick (`) characters for quoting identifiers instead
 * of the ANSI double-quote convention used by the base Grammar.
 */
class MySqlGrammar extends Grammar
{
    /**
     * {@inheritdoc}
     */
    protected function wrapValue(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        return '`' . str_replace('`', '``', $value) . '`';
    }
}
