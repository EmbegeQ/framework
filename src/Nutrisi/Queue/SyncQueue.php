<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use EmbegeQ\Nutrisi\Contracts\Queue\JobInterface;

class SyncQueue extends Queue
{
    /**
     * Push a new job onto the queue.
     */
    public function push(string|object $job, mixed $data = '', ?string $queue = null): mixed
    {
        $payload = $this->createPayload($job, $data);

        $this->resolveAndExecute($payload);

        return 'sync-' . bin2hex(random_bytes(8));
    }

    /**
     * Push a new job onto the queue after a delay.
     */
    public function later(int $delay, string|object $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        return null;
    }

    /**
     * Get the size of the queue.
     */
    public function size(?string $queue = null): int
    {
        return 0;
    }
}
