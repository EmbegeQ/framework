<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Events;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Events\DispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Event dispatcher implementation for EmbegeQ.
 */
class Dispatcher implements DispatcherInterface, ListenerProviderInterface
{
    /**
     * The container instance.
     *
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container;

    /**
     * The registered listeners, grouped by event name.
     *
     * @var array<string, array<int, callable|string>>
     */
    protected array $listeners = [];

    /**
     * Create a new event dispatcher instance.
     *
     * @param ContainerInterface|null $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param string $event
     * @param callable|string $listener
     */
    public function listen(string $event, callable|string $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && count($this->listeners[$event]) > 0;
    }

    /**
     * Dispatch an event and call all of its listeners.
     *
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $event;
    }

    /**
     * Get the listeners for a given event object.
     *
     * @param object $event
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventName = get_class($event);

        if (!$this->hasListeners($eventName)) {
            return [];
        }

        $resolved = [];
        foreach ($this->listeners[$eventName] as $listener) {
            $resolved[] = $this->resolveListener($listener);
        }

        return $resolved;
    }

    /**
     * Resolve a listener to a callable.
     *
     * @param callable|string $listener
     * @return callable
     */
    protected function resolveListener(callable|string $listener): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        $parts = explode('@', $listener);
        $class = $parts[0];
        $method = $parts[1] ?? 'handle';

        return function (object $event) use ($class, $method) {
            $instance = $this->container ? $this->container->get($class) : new $class();
            return $instance->{$method}($event);
        };
    }
}
