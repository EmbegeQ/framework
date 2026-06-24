<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Queue;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Queue\QueueInterface;

abstract class Queue implements QueueInterface
{
    /**
     * The container instance.
     */
    protected ContainerInterface $container;

    /**
     * Create a payload string for the job.
     */
    protected function createPayload(string|object $job, mixed $data = ''): string
    {
        if (is_object($job)) {
            return json_encode([
                'displayName' => get_class($job),
                'job' => get_class($job),
                'isObject' => true,
                'command' => serialize($job),
            ], JSON_THROW_ON_ERROR);
        }

        return json_encode([
            'displayName' => $job,
            'job' => $job,
            'isObject' => false,
            'data' => $data,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Set the container instance.
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Resolve and execute the job payload.
     */
    public function resolveAndExecute(string $payload): void
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if ($data['isObject'] ?? false) {
            $jobInstance = unserialize($data['command']);
            if (is_object($jobInstance)) {
                $method = method_exists($jobInstance, 'handle') ? 'handle' : 'fire';
                /** @var callable $callable */
                $callable = [$jobInstance, $method];
                call_user_func($callable);
            }
        } else {
            $class = $data['job'];
            $jobInstance = $this->container->get($class);
            if (is_object($jobInstance)) {
                $method = method_exists($jobInstance, 'handle') ? 'handle' : 'fire';
                /** @var callable $callable */
                $callable = [$jobInstance, $method];
                call_user_func($callable, $data['data'] ?? null);
            }
        }
    }
}
