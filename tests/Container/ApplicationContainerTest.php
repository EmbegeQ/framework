<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container;

use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Container\BindingResolutionException;
use EmbegeQ\Nutrisi\Container\EntryNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ApplicationContainer.
 *
 * Verifies binding registration, singleton caching, Reflection autowiring,
 * alias resolution, circular dependency detection, and ArrayAccess.
 */
class ApplicationContainerTest extends TestCase
{
    #[Test]
    public function it_can_bind_and_resolve_a_closure(): void
    {
        $container = new ApplicationContainer();

        $container->bind('greeting', fn () => 'Hello, EmbegeQ!');

        $this->assertSame('Hello, EmbegeQ!', $container->make('greeting'));
    }

    #[Test]
    public function it_can_bind_and_resolve_a_singleton(): void
    {
        $container = new ApplicationContainer();

        $container->singleton('counter', fn () => new \stdClass());

        $first = $container->make('counter');
        $second = $container->make('counter');

        $this->assertSame($first, $second);
    }

    #[Test]
    public function singleton_returns_same_instance_on_subsequent_calls(): void
    {
        $container = new ApplicationContainer();

        $callCount = 0;
        $container->singleton('service', function () use (&$callCount): \stdClass {
            $callCount++;

            return new \stdClass();
        });

        $container->make('service');
        $container->make('service');
        $container->make('service');

        $this->assertSame(1, $callCount, 'Singleton factory should only be called once.');
    }

    #[Test]
    public function it_can_register_and_retrieve_an_existing_instance(): void
    {
        $container = new ApplicationContainer();

        $obj = new \stdClass();
        $obj->name = 'test';

        $container->instance('my_object', $obj);

        $this->assertSame($obj, $container->make('my_object'));
    }

    #[Test]
    public function it_can_autowire_classes_via_reflection(): void
    {
        $container = new ApplicationContainer();

        $result = $container->make(Fixtures\SimpleClass::class);

        $this->assertInstanceOf(Fixtures\SimpleClass::class, $result);
    }

    #[Test]
    public function it_can_autowire_nested_dependencies(): void
    {
        $container = new ApplicationContainer();

        $result = $container->make(Fixtures\ClassWithDependency::class);

        $this->assertInstanceOf(Fixtures\ClassWithDependency::class, $result);
        $this->assertInstanceOf(Fixtures\SimpleClass::class, $result->dependency);
    }

    #[Test]
    public function it_can_autowire_interfaces_when_bound(): void
    {
        $container = new ApplicationContainer();

        $container->bind(
            Fixtures\DummyInterface::class,
            Fixtures\DummyImplementation::class,
        );

        $result = $container->make(Fixtures\ClassWithInterfaceDependency::class);

        $this->assertInstanceOf(Fixtures\ClassWithInterfaceDependency::class, $result);
        $this->assertInstanceOf(Fixtures\DummyImplementation::class, $result->service);
    }

    #[Test]
    public function it_throws_on_unresolvable_interface_without_binding(): void
    {
        $container = new ApplicationContainer();

        $this->expectException(BindingResolutionException::class);

        $container->make(Fixtures\DummyInterface::class);
    }

    #[Test]
    public function it_throws_on_non_existent_class(): void
    {
        $container = new ApplicationContainer();

        $this->expectException(BindingResolutionException::class);

        $container->make('NonExistent\\ClassName');
    }

    #[Test]
    public function it_resolves_parameters_with_default_values(): void
    {
        $container = new ApplicationContainer();

        $result = $container->make(Fixtures\ClassWithDefaults::class);

        $this->assertInstanceOf(Fixtures\ClassWithDefaults::class, $result);
        $this->assertSame('default', $result->value);
    }

    #[Test]
    public function it_supports_parameter_overrides(): void
    {
        $container = new ApplicationContainer();

        $result = $container->make(Fixtures\ClassWithDefaults::class, ['value' => 'overridden']);

        $this->assertSame('overridden', $result->value);
    }

    #[Test]
    public function it_detects_circular_dependencies(): void
    {
        $container = new ApplicationContainer();

        $this->expectException(BindingResolutionException::class);

        $container->make(Fixtures\CircularA::class);
    }

    #[Test]
    public function it_resolves_aliases(): void
    {
        $container = new ApplicationContainer();

        $container->singleton('original', fn () => 'resolved_value');
        $container->alias('original', 'my_alias');

        $this->assertSame('resolved_value', $container->make('my_alias'));
    }

    #[Test]
    public function flush_clears_all_bindings_and_instances(): void
    {
        $container = new ApplicationContainer();

        $container->singleton('service', fn () => new \stdClass());
        $container->make('service');

        $container->flush();

        $this->assertFalse($container->bound('service'));
    }

    #[Test]
    public function psr11_get_throws_entry_not_found(): void
    {
        $container = new ApplicationContainer();

        $this->expectException(EntryNotFoundException::class);

        $container->get('nonexistent');
    }

    #[Test]
    public function psr11_has_returns_correct_values(): void
    {
        $container = new ApplicationContainer();

        $this->assertFalse($container->has('nonexistent'));

        $container->bind('existing', fn () => 'value');
        $this->assertTrue($container->has('existing'));

        // Concrete classes are resolvable via Reflection.
        $this->assertTrue($container->has(Fixtures\SimpleClass::class));
    }

    #[Test]
    public function array_access_works_correctly(): void
    {
        $container = new ApplicationContainer();

        // offsetSet -> instance()
        $container['my_key'] = 'my_value';
        $this->assertTrue(isset($container['my_key']));
        $this->assertSame('my_value', $container['my_key']);

        // offsetUnset
        unset($container['my_key']);
        $this->assertFalse($container->bound('my_key'));
    }
}
