<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Database;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;
use EmbegeQ\Nutrisi\Database\Query\Grammars\MySqlGrammar;
use EmbegeQ\Nutrisi\Database\Query\Grammars\PostgresGrammar;
use EmbegeQ\Nutrisi\Database\Query\Grammars\SQLiteGrammar;
use InvalidArgumentException;
use PDO;

/**
 * Database Manager responsible for resolving and caching PDO connections.
 *
 * Reads connection configuration from the application's config repository
 * (key: `database.connections.*`) and lazily creates Connection instances.
 * Supports custom connection resolvers via `extend()`.
 *
 * Designed for Application-scope: a single DatabaseManager lives for the
 * entire worker lifetime. Individual connections are cached but can be
 * purged and reconnected.
 */
class DatabaseManager implements ConnectionResolverInterface
{
    /**
     * The resolved connection instances.
     *
     * @var array<string, Connection>
     */
    protected array $connections = [];

    /**
     * Custom connection resolvers.
     *
     * @var array<string, callable>
     */
    protected array $extensions = [];

    /**
     * Create a new DatabaseManager instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * Get a database connection instance.
     */
    public function connection(?string $name = null): ConnectionInterface
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnection(): string
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        return (string) $config->get('database.default', 'sqlite');
    }

    /**
     * Set the default connection name.
     */
    public function setDefaultConnection(string $name): void
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        $config->set('database.default', $name);
    }

    /**
     * Disconnect from the given database and remove it from local cache.
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
        }
    }

    /**
     * Disconnect from the given database.
     */
    public function disconnect(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
     */
    public function reconnect(?string $name = null): ConnectionInterface
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->purge($name);

        return $this->connection($name);
    }

    /**
     * Register a custom connection resolver.
     */
    public function extend(string $name, callable $resolver): void
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Get all of the created connections.
     *
     * @return array<string, Connection>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Build and configure a Connection instance from configuration.
     *
     * @throws InvalidArgumentException
     */
    protected function makeConnection(string $name): Connection
    {
        $config = $this->configuration($name);

        // Check for a custom resolver registered by name.
        if (isset($this->extensions[$name])) {
            return $this->configure(
                ($this->extensions[$name])($config, $name),
                $config
            );
        }

        $driver = $config['driver'] ?? '';

        // Check for a custom resolver registered by driver type.
        if (isset($this->extensions[$driver])) {
            return $this->configure(
                ($this->extensions[$driver])($config, $name),
                $config
            );
        }

        return $this->configure(
            $this->createConnection($config),
            $config
        );
    }

    /**
     * Create a PDO-based Connection from the given config.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidArgumentException
     */
    protected function createConnection(array $config): Connection
    {
        $driver = $config['driver'] ?? '';

        $pdo = function () use ($config, $driver): PDO {
            return $this->createPdoConnection($config, $driver);
        };

        $database = (string) ($config['database'] ?? '');
        $prefix = (string) ($config['prefix'] ?? '');

        return new Connection($pdo, $database, $prefix, $config);
    }

    /**
     * Create the actual PDO instance based on the driver.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidArgumentException
     */
    protected function createPdoConnection(array $config, string $driver): PDO
    {
        $dsn = $this->buildDsn($config, $driver);
        $username = isset($config['username']) ? (string) $config['username'] : null;
        $password = isset($config['password']) ? (string) $config['password'] : null;

        /** @var array<int, mixed> $options */
        $options = (array) ($config['options'] ?? []);

        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $options[PDO::ATTR_EMULATE_PREPARES] = false;

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Build the DSN string for a given driver and config.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidArgumentException
     */
    protected function buildDsn(array $config, string $driver): string
    {
        return match ($driver) {
            'sqlite' => 'sqlite:' . ($config['database'] ?? ':memory:'),
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '3306',
                $config['database'] ?? '',
                $config['charset'] ?? 'utf8mb4',
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '5432',
                $config['database'] ?? '',
            ),
            default => throw new InvalidArgumentException(
                "Unsupported database driver [{$driver}]."
            ),
        };
    }

    /**
     * Apply driver-specific grammar to the connection.
     *
     * @param array<string, mixed> $config
     */
    protected function configure(Connection $connection, array $config): Connection
    {
        $driver = $config['driver'] ?? '';

        $grammar = match ($driver) {
            'sqlite' => new SQLiteGrammar($connection),
            'mysql' => new MySqlGrammar($connection),
            'pgsql' => new PostgresGrammar($connection),
            default => $connection->getQueryGrammar(),
        };

        $connection->setQueryGrammar($grammar);

        return $connection;
    }

    /**
     * Get the configuration for a connection.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    protected function configuration(string $name): array
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        /** @var array<string, mixed>|null $connectionConfig */
        $connectionConfig = $config->get("database.connections.{$name}");

        if ($connectionConfig === null) {
            throw new InvalidArgumentException(
                "Database connection [{$name}] not configured."
            );
        }

        return $connectionConfig;
    }
}
