<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;
use EmbegeQ\Nutrisi\Contracts\Queue\QueueInterface;
use InvalidArgumentException;

class QueueManager
{
    /**
     * The resolved connections.
     *
     * @var array<string, QueueInterface>
     */
    protected array $connections = [];

    /**
     * Create a new queue manager instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * Resolve a queue connection.
     */
    public function connection(?string $name = null): QueueInterface
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve the queue connection instance.
     */
    protected function resolve(string $name): QueueInterface
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new InvalidArgumentException("Queue connection [{$name}] is not configured.");
        }

        $driver = $config['driver'] ?? null;

        if ($driver === null) {
            throw new InvalidArgumentException("Queue connection [{$name}] is missing a driver configuration.");
        }

        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            $queue = $this->$method($config);
            $queue->setContainer($this->container);
            return $queue;
        }

        throw new InvalidArgumentException("Queue driver [{$driver}] is not supported.");
    }

    /**
     * Create a sync queue driver connection.
     *
     * @param array<string, mixed> $config
     * @return QueueInterface
     */
    protected function createSyncDriver(array $config): QueueInterface
    {
        return new SyncQueue();
    }

    /**
     * Create a database queue driver connection.
     *
     * @param array<string, mixed> $config
     * @return QueueInterface
     */
    protected function createDatabaseDriver(array $config): QueueInterface
    {
        $resolver = $this->container->get(ConnectionResolverInterface::class);
        $connection = $resolver->connection($config['connection'] ?? null);

        return new DatabaseQueue($connection, $config['table'] ?? 'jobs');
    }

    /**
     * Get the default queue connection name.
     */
    public function getDefaultConnection(): string
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get(RepositoryInterface::class);

        return (string) $config->get('queue.default', 'sync');
    }

    /**
     * Get the queue connection configuration.
     *
     * @param string $name
     * @return array<string, mixed>|null
     */
    protected function getConfig(string $name): ?array
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get(RepositoryInterface::class);

        return $config->get("queue.connections.{$name}");
    }
}
