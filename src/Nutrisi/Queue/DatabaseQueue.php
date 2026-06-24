<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use EmbegeQ\Nutrisi\Contracts\Database\ConnectionInterface;
use EmbegeQ\Nutrisi\Contracts\Queue\JobInterface;

class DatabaseQueue extends Queue
{
    /**
     * Create a new database queue instance.
     */
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $table = 'jobs'
    ) {}

    /**
     * Push a new job onto the queue.
     */
    public function push(string|object $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->pushToDatabase($queue ?: 'default', $this->createPayload($job, $data));
    }

    /**
     * Push a new job onto the queue after a delay.
     */
    public function later(int $delay, string|object $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->pushToDatabase($queue ?: 'default', $this->createPayload($job, $data), $delay);
    }

    /**
     * Pop the next job off of the queue.
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        $queueName = $queue ?: 'default';

        return $this->connection->transaction(function () use ($queueName) {
            $now = time();
            $expiry = $now - 90;

            // Database agnostic SQL to find next available job
            $sql = "SELECT * FROM {$this->table} " .
                   "WHERE queue = ? AND (reserved_at IS NULL OR reserved_at <= ?) AND available_at <= ? " .
                   "ORDER BY id ASC LIMIT 1";

            $job = $this->connection->selectOne($sql, [$queueName, $expiry, $now]);

            if ($job) {
                // Cast to array in case driver returns object/stdClass
                $job = (array) $job;

                $this->connection->table($this->table)->where('id', $job['id'])->update([
                    'reserved_at' => $now,
                    'attempts' => (int) $job['attempts'] + 1,
                ]);

                $job['attempts'] = (int) $job['attempts'] + 1;

                return new DatabaseJob($this, $job, $queueName);
            }

            return null;
        });
    }

    /**
     * Get the size of the queue.
     */
    public function size(?string $queue = null): int
    {
        return (int) $this->connection->table($this->table)
            ->where('queue', $queue ?: 'default')
            ->count();
    }

    /**
     * Push a raw payload to the database.
     */
    protected function pushToDatabase(string $queue, string $payload, int $delay = 0): int
    {
        $now = time();

        $this->connection->table($this->table)->insert([
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $now + $delay,
            'created_at' => $now,
        ]);

        return (int) $this->connection->getPdo()->lastInsertId();
    }

    /**
     * Delete a reserved job from the database.
     */
    public function deleteReserved(int $id): void
    {
        $this->connection->table($this->table)->where('id', $id)->delete();
    }

    /**
     * Release a reserved job back onto the queue.
     */
    public function release(int $id, int $delay, int $attempts): void
    {
        $this->connection->table($this->table)->where('id', $id)->update([
            'reserved_at' => null,
            'available_at' => time() + $delay,
            'attempts' => $attempts,
        ]);
    }
}
