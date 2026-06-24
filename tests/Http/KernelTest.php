<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Http;

use EmbegeQ\Nutrisi\Contracts\Foundation\ApplicationInterface;
use EmbegeQ\Nutrisi\Http\Kernel;
use EmbegeQ\Nutrisi\Routing\Router;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class KernelTest extends TestCase
{
    #[Test]
    public function it_can_process_requests_through_global_middleware_and_router(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $router = new Router();

        $router->get('/home', function () {
            return 'home-response';
        });

        // Define a kernel sub-class with specific global middleware.
        $kernel = new class($app, $router) extends Kernel {
            protected array $middleware = [
                'TestMiddleware',
            ];
        };

        $calls = [];

        $testMiddleware = new class($calls) implements MiddlewareInterface {
            public function __construct(private array &$calls) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->calls[] = 'global';
                return $handler->handle($request);
            }
        };

        // Mock container to resolve the string middleware.
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['TestMiddleware', $testMiddleware],
            ]);

        $request = new ServerRequest('GET', '/home');
        $request = $request->withAttribute(ContainerInterface::class, $container);

        $response = $kernel->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('home-response', (string) $response->getBody());
        $this->assertSame(['global'], $calls);
    }
}
