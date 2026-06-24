<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Queue;

/**
 * Queue driver interface for EmbegeQ.
 */
interface QueueInterface
{
    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(string|object $job, mixed $data = '', ?string $queue = null): mixed;

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay The delay in seconds.
     * @param string|object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(int $delay, string|object $job, mixed $data = '', ?string $queue = null): mixed;

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface;

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int;
}
