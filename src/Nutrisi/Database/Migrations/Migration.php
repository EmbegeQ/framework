<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Migrations;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;

abstract class Migration
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    abstract public function up(): void;

    abstract public function down(): void;

    public function getName(): string
    {
        return static::class;
    }
}
