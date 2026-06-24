<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Queue;

/**
 * Job interface representing a single queued job.
 */
interface JobInterface
{
    /**
     * Execute the job.
     */
    public function fire(): void;

    /**
     * Delete the job from the queue.
     */
    public function delete(): void;

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     */
    public function release(int $delay = 0): void;

    /**
     * Get the number of times the job has been attempted.
     */
    public function attempts(): int;

    /**
     * Get the job identifier.
     */
    public function getId(): string;

    /**
     * Get the name of the queued job class.
     */
    public function getName(): string;

    /**
     * Get the name of the queue the job belongs to.
     */
    public function getQueue(): string;
}
