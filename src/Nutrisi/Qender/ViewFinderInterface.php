<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender;

interface ViewFinderInterface
{
    /**
     * Find a view path by dot-notated name.
     *
     * @throws \RuntimeException if view not found
     */
    public function find(string $view): string;
}
