<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Events;

use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Events\DispatcherInterface;
use EmbegeQ\Nutrisi\Events\Dispatcher;
use EmbegeQ\Nutrisi\Events\EventServiceProvider;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    protected ApplicationContainer $container;
    protected Dispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ApplicationContainer();
        $provider = new EventServiceProvider();
        $provider->register($this->container);
        $this->dispatcher = $this->container->get(Dispatcher::class);
    }

    public function test_dispatcher_is_registered_correctly(): void
    {
        $this->assertInstanceOf(Dispatcher::class, $this->dispatcher);
        $this->assertSame($this->dispatcher, $this->container->get('events'));
        $this->assertSame($this->dispatcher, $this->container->get(DispatcherInterface::class));
    }

    public function test_it_dispatches_events_to_closure_listeners(): void
    {
        $event = new class {
            public int $counter = 0;
        };

        $this->dispatcher->listen($event::class, function ($e) {
            $e->counter++;
        });

        $this->dispatcher->dispatch($event);

        $this->assertSame(1, $event->counter);
    }

    public function test_it_dispatches_events_to_class_listeners(): void
    {
        $event = new class {
            public int $value = 0;
        };

        // Register class listener.
        $this->dispatcher->listen($event::class, StubListener::class . '@handle');

        // Resolve StubListener in container.
        $this->container->bind(StubListener::class, function () {
            return new StubListener(42);
        });

        $this->dispatcher->dispatch($event);

        $this->assertSame(42, $event->value);
    }
}

class StubListener
{
    public function __construct(protected int $amount) {}

    public function handle(object $event): void
    {
        $event->value = $this->amount;
    }
}
