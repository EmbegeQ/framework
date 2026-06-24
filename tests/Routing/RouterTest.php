<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Routing;

use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Container\RequestContainer;
use EmbegeQ\Nutrisi\Routing\Router;
use EmbegeQ\Nutrisi\Routing\Route;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterTest extends TestCase
{
    #[Test]
    public function it_can_register_and_dispatch_basic_routes(): void
    {
        $app = new ApplicationContainer();
        $requestContainer = new RequestContainer($app);

        $router = new Router();
        $router->get('/hello', function () {
            return 'world';
        });

        $request = new ServerRequest('GET', '/hello');
        $request = $request->withAttribute(ContainerInterface::class, $requestContainer);

        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('world', (string) $response->getBody());
    }

    #[Test]
    public function it_can_extract_route_parameters_and_inject_them(): void
    {
        $app = new ApplicationContainer();
        $requestContainer = new RequestContainer($app);

        $router = new Router();
        $router->get('/users/{id}', function (string $id, ServerRequestInterface $request) {
            return sprintf('user:%s:%s', $id, $request->getAttribute('id'));
        });

        $request = new ServerRequest('GET', '/users/42');
        $request = $request->withAttribute(ContainerInterface::class, $requestContainer);

        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('user:42:42', (string) $response->getBody());
    }

    #[Test]
    public function it_supports_nested_groups_with_prefix_and_middleware(): void
    {
        $app = new ApplicationContainer();
        $app->bind('group_mid', function () {
            return new class implements \Psr\Http\Server\MiddlewareInterface {
                public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
                {
                    return $handler->handle($request);
                }
            };
        });
        $app->bind('v1_mid', function () {
            return new class implements \Psr\Http\Server\MiddlewareInterface {
                public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
                {
                    return $handler->handle($request);
                }
            };
        });

        $requestContainer = new RequestContainer($app);

        $router = new Router();
        $router->group(['prefix' => '/api', 'middleware' => 'group_mid'], function (Router $r) {
            $r->group(['prefix' => '/v1', 'middleware' => 'v1_mid'], function (Router $r) {
                $r->get('/users', function (ServerRequestInterface $request) {
                    $route = $request->getAttribute(Route::class);
                    return implode(',', $route->getMiddleware());
                });
            });
        });

        $request = new ServerRequest('GET', '/api/v1/users');
        $request = $request->withAttribute(ContainerInterface::class, $requestContainer);

        $response = $router->dispatch($request);

        $this->assertSame('/api/v1/users', $router->addRoute(['POST'], '/api/v1/users', 'test')->getUri());
        $this->assertSame('group_mid,v1_mid', (string) $response->getBody());
    }

    #[Test]
    public function it_resolves_controllers_and_autowires_methods_from_the_container(): void
    {
        $app = new ApplicationContainer();
        $requestContainer = new RequestContainer($app);

        // Bind the Controller class into the application container.
        $app->singleton(FakeUserController::class);

        $router = new Router();
        $router->get('/fake/users/{id}', [FakeUserController::class, 'show']);

        $request = new ServerRequest('GET', '/fake/users/99');
        $request = $request->withAttribute(ContainerInterface::class, $requestContainer);

        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('id:99;request:yes;container:yes', (string) $response->getBody());
    }

    #[Test]
    public function it_resolves_request_scoped_dependencies_in_autowired_controllers(): void
    {
        $app = new ApplicationContainer();
        $requestContainer = new RequestContainer($app);

        // Bind a request-scoped dependency into the request container.
        $requestContainer->instance(RequestScopedDependency::class, new RequestScopedDependency('req-value'));

        $app->singleton(FakeUserController::class);

        $router = new Router();
        $router->get('/fake-scoped', [FakeUserController::class, 'showScoped']);

        $request = new ServerRequest('GET', '/fake-scoped');
        $request = $request->withAttribute(ContainerInterface::class, $requestContainer);

        $response = $router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('dependency-value:req-value', (string) $response->getBody());
    }
}

class FakeUserController
{
    public function show(string $id, ServerRequestInterface $request, ContainerInterface $container): string
    {
        $hasRequest = $request instanceof ServerRequestInterface ? 'yes' : 'no';
        $hasContainer = $container instanceof ContainerInterface ? 'yes' : 'no';

        return sprintf('id:%s;request:%s;container:%s', $id, $hasRequest, $hasContainer);
    }

    public function showScoped(RequestScopedDependency $dependency): string
    {
        return 'dependency-value:' . $dependency->getValue();
    }
}

class RequestScopedDependency
{
    public function __construct(private string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }
}
