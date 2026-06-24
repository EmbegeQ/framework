<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use Throwable;

class Worker
{
    /**
     * Create a new queue worker instance.
     */
    public function __construct(protected QueueManager $manager) {}

    /**
     * Run a single job from the queue.
     *
     * @param string $connectionName
     * @param string $queue
     * @return bool
     *
     * @throws Throwable
     */
    public function runNextJob(string $connectionName, string $queue): bool
    {
        $connection = $this->manager->connection($connectionName);

        try {
            $job = $connection->pop($queue);

            if ($job !== null) {
                try {
                    $job->fire();
                    $job->delete();
                    return true;
                } catch (Throwable $e) {
                    if ($job->attempts() < 3) {
                        $job->release(5); // retry after 5 seconds
                    } else {
                        $job->delete();
                    }
                    throw $e;
                }
            }
        } catch (Throwable $e) {
            throw $e;
        }

        return false;
    }
}
