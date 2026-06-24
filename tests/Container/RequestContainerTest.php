<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Container;

use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Container\EntryNotFoundException;
use EmbegeQ\Nutrisi\Container\RequestContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the RequestContainer.
 *
 * Verifies request-scope isolation, fallback delegation to ApplicationContainer,
 * and memory safety (no data leaks between request containers).
 */
class RequestContainerTest extends TestCase
{
    #[Test]
    public function request_container_resolves_local_bindings_first(): void
    {
        $app = new ApplicationContainer();
        $app->instance('locale', 'en');

        $request = new RequestContainer($app);
        $request->instance('locale', 'id'); // Override in request scope.

        $this->assertSame('id', $request->get('locale'));
        $this->assertSame('en', $app->get('locale'), 'Application scope must not be affected.');
    }

    #[Test]
    public function request_container_delegates_to_application_container(): void
    {
        $app = new ApplicationContainer();
        $app->instance('app_service', 'from_application');

        $request = new RequestContainer($app);

        $this->assertSame('from_application', $request->get('app_service'));
    }

    #[Test]
    public function request_container_has_checks_both_scopes(): void
    {
        $app = new ApplicationContainer();
        $app->instance('app_only', 'value');

        $request = new RequestContainer($app);
        $request->instance('req_only', 'value');

        $this->assertTrue($request->has('app_only'));
        $this->assertTrue($request->has('req_only'));
        $this->assertFalse($request->has('nonexistent'));
    }

    #[Test]
    public function request_container_isolation_between_instances(): void
    {
        $app = new ApplicationContainer();

        $request1 = new RequestContainer($app);
        $request1->instance('user_id', 42);

        $request2 = new RequestContainer($app);
        $request2->instance('user_id', 99);

        $this->assertSame(42, $request1->get('user_id'));
        $this->assertSame(99, $request2->get('user_id'));
    }

    #[Test]
    public function request_container_throws_when_entry_not_found_in_either_scope(): void
    {
        $app = new ApplicationContainer();
        $request = new RequestContainer($app);

        $this->expectException(EntryNotFoundException::class);

        $request->get('nonexistent_service');
    }

    #[Test]
    public function request_container_can_store_null_values(): void
    {
        $app = new ApplicationContainer();
        $request = new RequestContainer($app);

        $request->instance('nullable_service', null);

        $this->assertTrue($request->has('nullable_service'));
        $this->assertNull($request->get('nullable_service'));
    }
}
