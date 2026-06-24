<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Http;

use EmbegeQ\Nutrisi\Http\MiddlewareDispatcher;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareDispatcherTest extends TestCase
{
    #[Test]
    public function it_executes_middlewares_in_fifo_order(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $calls = [];

        $middleware1 = new class($calls) implements MiddlewareInterface {
            public function __construct(private array &$calls) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->calls[] = 'first';
                return $handler->handle($request);
            }
        };

        $middleware2 = new class($calls) implements MiddlewareInterface {
            public function __construct(private array &$calls) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->calls[] = 'second';
                return $handler->handle($request);
            }
        };

        $fallback = new class($calls) implements RequestHandlerInterface {
            public function __construct(private array &$calls) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->calls[] = 'fallback';
                return new Response(200, [], 'done');
            }
        };

        $dispatcher = new MiddlewareDispatcher(
            $container,
            [$middleware1, $middleware2],
            $fallback
        );

        $request = new ServerRequest('GET', '/test');
        $response = $dispatcher->handle($request);

        $this->assertSame('done', (string) $response->getBody());
        $this->assertSame(['first', 'second', 'fallback'], $calls);
    }

    #[Test]
    public function it_resolves_string_middlewares_from_the_container(): void
    {
        $calls = [];

        $middleware = new class($calls) implements MiddlewareInterface {
            public function __construct(private array &$calls) {}

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->calls[] = 'resolved';
                return $handler->handle($request);
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('MockMiddleware')
            ->willReturn($middleware);

        $fallback = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $dispatcher = new MiddlewareDispatcher(
            $container,
            ['MockMiddleware'],
            $fallback
        );

        $request = new ServerRequest('GET', '/test');
        $dispatcher->handle($request);

        $this->assertSame(['resolved'], $calls);
    }

    #[Test]
    public function it_can_execute_callable_middlewares(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $calls = [];

        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use (&$calls): ResponseInterface {
            $calls[] = 'callable';
            return $handler->handle($request);
        };

        $fallback = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'done');
            }
        };

        $dispatcher = new MiddlewareDispatcher(
            $container,
            [$callable],
            $fallback
        );

        $request = new ServerRequest('GET', '/test');
        $response = $dispatcher->handle($request);

        $this->assertSame('done', (string) $response->getBody());
        $this->assertSame(['callable'], $calls);
    }
}
