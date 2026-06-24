<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Queue;

use EmbegeQ\Nutrisi\Config\Repository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;
use EmbegeQ\Nutrisi\Database\DatabaseManager;
use EmbegeQ\Nutrisi\Database\DatabaseServiceProvider;
use EmbegeQ\Nutrisi\Queue\QueueManager;
use EmbegeQ\Nutrisi\Queue\QueueServiceProvider;
use EmbegeQ\Nutrisi\Queue\Worker;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    protected ApplicationContainer $container;
    protected QueueManager $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ApplicationContainer();

        // Bind config
        $config = new Repository([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
            'queue' => [
                'default' => 'database',
                'connections' => [
                    'sync' => [
                        'driver' => 'sync',
                    ],
                    'database' => [
                        'driver' => 'database',
                        'connection' => 'sqlite',
                        'table' => 'jobs',
                    ],
                ],
            ],
        ]);

        $this->container->instance(RepositoryInterface::class, $config);
        $this->container->alias(RepositoryInterface::class, 'config');

        // Register Database & Queue providers
        (new DatabaseServiceProvider())->register($this->container);
        (new QueueServiceProvider())->register($this->container);

        $this->queue = $this->container->get(QueueManager::class);

        // Create the jobs table in memory sqlite
        $db = $this->container->get(ConnectionResolverInterface::class);
        $db->connection()->unprepared("
            CREATE TABLE jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                attempts INTEGER NOT NULL,
                reserved_at INTEGER NULL,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )
        ");
    }

    public function test_queue_manager_resolves_drivers(): void
    {
        $this->assertInstanceOf(QueueManager::class, $this->queue);
        $this->assertInstanceOf(\EmbegeQ\Nutrisi\Queue\SyncQueue::class, $this->queue->connection('sync'));
        $this->assertInstanceOf(\EmbegeQ\Nutrisi\Queue\DatabaseQueue::class, $this->queue->connection('database'));
    }

    public function test_sync_queue_runs_immediately(): void
    {
        $_SERVER['__sync_test'] = 0;

        $sync = $this->queue->connection('sync');
        $sync->push(SyncStubJob::class, ['amount' => 5]);

        $this->assertSame(5, $_SERVER['__sync_test']);
        unset($_SERVER['__sync_test']);
    }

    public function test_database_queue_pushes_pops_and_releases(): void
    {
        $dbQueue = $this->queue->connection('database');

        $this->assertSame(0, $dbQueue->size());

        // Push a job
        $dbQueue->push(SyncStubJob::class, ['amount' => 10]);
        $this->assertSame(1, $dbQueue->size());

        // Pop the job
        $job = $dbQueue->pop();
        $this->assertNotNull($job);
        $this->assertSame(SyncStubJob::class, $job->getName());
        $this->assertSame(1, $job->attempts());

        // Release the job back
        $job->release(10); // delay 10 seconds

        // Pop should be null because it is delayed
        $this->assertNull($dbQueue->pop());

        // Delete the job manually
        $job->delete();
        $this->assertSame(0, $dbQueue->size());
    }

    public function test_worker_executes_next_job(): void
    {
        $_SERVER['__worker_test'] = 0;

        $dbQueue = $this->queue->connection('database');
        $dbQueue->push(SyncStubJob::class, ['amount' => 7]);

        $worker = $this->container->get(Worker::class);
        $result = $worker->runNextJob('database', 'default');

        $this->assertTrue($result);
        $this->assertSame(7, $_SERVER['__worker_test']);

        unset($_SERVER['__worker_test']);
    }
}

class SyncStubJob
{
    /**
     * Handle the stub job.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public function handle(array $data): void
    {
        if (isset($_SERVER['__sync_test'])) {
            $_SERVER['__sync_test'] += $data['amount'];
        }
        if (isset($_SERVER['__worker_test'])) {
            $_SERVER['__worker_test'] += $data['amount'];
        }
    }
}
