<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use EmbegeQ\Nutrisi\Contracts\Queue\JobInterface;

class DatabaseJob implements JobInterface
{
    /**
     * Create a new database job instance.
     *
     * @param DatabaseQueue $queue
     * @param array<string, mixed> $job
     * @param string $queueName
     */
    public function __construct(
        protected DatabaseQueue $queue,
        protected array $job,
        protected string $queueName
    ) {}

    /**
     * {@inheritdoc}
     */
    public function fire(): void
    {
        $payload = (string) $this->job['payload'];
        $this->queue->resolveAndExecute($payload);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): void
    {
        $this->queue->deleteReserved((int) $this->job['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function release(int $delay = 0): void
    {
        $this->queue->release((int) $this->job['id'], $delay, $this->attempts());
    }

    /**
     * {@inheritdoc}
     */
    public function attempts(): int
    {
        return (int) $this->job['attempts'];
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return (string) $this->job['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        $payload = json_decode((string) $this->job['payload'], true);
        return (string) ($payload['job'] ?? 'Unknown');
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue(): string
    {
        return $this->queueName;
    }
}
