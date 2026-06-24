<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database\Migrations;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;
use PDO;

class MigrationRunner
{
    private ContainerInterface $container;
    private ConnectionResolverInterface $resolver;
    private PDO $pdo;
    private string $migrationsPath;
    private string $tableName = 'migrations';

    public function __construct(ContainerInterface $container, string $migrationsPath)
    {
        $this->container = $container;
        $this->resolver = $container->get(ConnectionResolverInterface::class);
        $this->pdo = $this->resolver->connection()->getPdo();
        $this->migrationsPath = $migrationsPath;
    }

    public function run(): int
    {
        $this->createMigrationsTable();
        $executed = 0;

        foreach ($this->getPendingMigrations() as $migration) {
            $instance = $this->loadMigration($migration);
            $instance->up();
            $this->recordMigration($migration);
            $executed++;
        }

        return $executed;
    }

    public function rollback(int $steps = 1): int
    {
        $this->createMigrationsTable();
        $migrations = $this->getExecutedMigrations($steps);
        $rolled = 0;

        foreach (array_reverse($migrations) as $migration) {
            $instance = $this->loadMigration($migration['migration']);
            $instance->down();
            $this->deleteMigration($migration['migration']);
            $rolled++;
        }

        return $rolled;
    }

    public function reset(): int
    {
        $this->createMigrationsTable();
        $migrations = $this->getAllExecutedMigrations();
        $rolled = 0;

        foreach (array_reverse($migrations) as $migration) {
            $instance = $this->loadMigration($migration['migration']);
            $instance->down();
            $this->deleteMigration($migration['migration']);
            $rolled++;
        }

        return $rolled;
    }

    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT NOT NULL UNIQUE,
            batch INTEGER NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->pdo->exec($sql);
    }

    private function getPendingMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*_*.php') ?: [];
        $pending = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!$this->recordExists($name)) {
                $pending[] = $name;
            }
        }

        return $pending;
    }

    private function getExecutedMigrations(int $limit): array
    {
        $sql = "SELECT migration FROM {$this->tableName} ORDER BY id DESC LIMIT " . intval($limit);
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getAllExecutedMigrations(): array
    {
        $sql = "SELECT migration FROM {$this->tableName} ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function recordExists(string $migration): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tableName} WHERE migration = ?");
        $stmt->execute([$migration]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function recordMigration(string $migration): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->tableName} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $this->getNextBatch()]);
    }

    private function deleteMigration(string $migration): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    private function getNextBatch(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as batch FROM {$this->tableName}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return ((int) ($result['batch'] ?? 0)) + 1;
    }

    private function loadMigration(string $name): Migration
    {
        $filename = $this->migrationsPath . '/' . $name . '.php';

        if (!file_exists($filename)) {
            throw new \RuntimeException("Migration file {$filename} does not exist.");
        }

        require_once $filename;

        $class = 'EmbegeQ\\Nutrisi\\Database\\Migrations\\' . $name;

        if (!class_exists($class)) {
            throw new \RuntimeException("Migration class {$class} not found.");
        }

        return new $class($this->container);
    }
}
