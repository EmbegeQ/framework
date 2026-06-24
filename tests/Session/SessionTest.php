<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Session;

use EmbegeQ\Nutrisi\Config\Repository as ConfigRepository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Container\RequestContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface as EmbegeQContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Session\SessionInterface;
use EmbegeQ\Nutrisi\Session\ArraySessionHandler;
use EmbegeQ\Nutrisi\Session\FileSessionHandler;
use EmbegeQ\Nutrisi\Session\Middleware\StartSessionMiddleware;
use EmbegeQ\Nutrisi\Session\SessionManager;
use EmbegeQ\Nutrisi\Session\Store;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionTest extends TestCase
{
    protected string $tempSessionDir;

    protected function setUp(): void
    {
        $this->tempSessionDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'embegeq_sessions_' . uniqid();
        @mkdir($this->tempSessionDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempSessionDir)) {
            $files = glob($this->tempSessionDir . DIRECTORY_SEPARATOR . '*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->tempSessionDir);
        }
    }

    #[Test]
    public function it_can_set_and_get_session_attributes(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store('embegeq_session', $handler);

        $store->start();
        $store->put('key1', 'value1');
        $store->put('key2', ['nested' => 'array']);

        $this->assertTrue($store->has('key1'));
        $this->assertSame('value1', $store->get('key1'));
        $this->assertSame(['nested' => 'array'], $store->get('key2'));
        $this->assertSame('default', $store->get('key3', 'default'));
    }

    #[Test]
    public function it_generates_and_regenerates_csrf_token(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store('embegeq_session', $handler);

        $store->start();
        $token1 = $store->token();
        $this->assertNotEmpty($token1);

        $store->regenerateToken();
        $token2 = $store->token();
        $this->assertNotSame($token1, $token2);
    }

    #[Test]
    public function it_saves_session_data_to_handler(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store('embegeq_session', $handler);

        $store->start();
        $store->put('user_id', 42);
        $store->save();

        $rawStored = $handler->read($store->getId());
        $this->assertNotEmpty($rawStored);

        $store2 = new Store('embegeq_session', $handler, $store->getId());
        $store2->start();
        $this->assertSame(42, $store2->get('user_id'));
    }

    #[Test]
    public function it_uses_file_handler_to_read_and_write(): void
    {
        $handler = new FileSessionHandler($this->tempSessionDir, 120);
        $store = new Store('embegeq_session', $handler);

        $store->start();
        $store->put('foo', 'bar');
        $store->save();

        $sessionFile = $this->tempSessionDir . DIRECTORY_SEPARATOR . $store->getId();
        $this->assertFileExists($sessionFile);

        $store2 = new Store('embegeq_session', $handler, $store->getId());
        $store2->start();
        $this->assertSame('bar', $store2->get('foo'));
    }

    #[Test]
    public function session_store_does_not_leak_across_request_containers(): void
    {
        // 1. Setup Application Container
        $app = new ApplicationContainer();
        $config = new ConfigRepository([
            'session' => [
                'driver' => 'array',
                'cookie' => 'embegeq_session',
            ],
        ]);
        $app->instance(RepositoryInterface::class, $config);
        $app->alias(RepositoryInterface::class, 'config');
        $app->instance('config', $config);

        $sessionManager = new SessionManager($app);
        $app->instance(SessionManager::class, $sessionManager);

        // 2. Setup Request Container A and execute Request A
        $requestContainerA = new RequestContainer($app);
        $requestA = (new ServerRequest('GET', '/'))
            ->withAttribute(PsrContainerInterface::class, $requestContainerA)
            ->withAttribute(EmbegeQContainerInterface::class, $requestContainerA);

        $middleware = new StartSessionMiddleware($sessionManager, $app);

        $handlerA = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $session = $request->getAttribute('session');
                $session->put('shared_data', 'secret_a');
                return new Response(200);
            }
        };

        $responseA = $middleware->process($requestA, $handlerA);

        // Extract session cookie A
        $cookieHeaderA = $responseA->getHeaderLine('Set-Cookie');
        $this->assertStringContainsString('embegeq_session=', $cookieHeaderA);

        preg_match('/embegeq_session=([a-f0-9]{40})/', $cookieHeaderA, $matchesA);
        $sessionIdA = $matchesA[1];

        // 3. Setup Request Container B and execute Request B
        $requestContainerB = new RequestContainer($app);
        $requestB = (new ServerRequest('GET', '/'))
            ->withAttribute(PsrContainerInterface::class, $requestContainerB)
            ->withAttribute(EmbegeQContainerInterface::class, $requestContainerB);

        $handlerB = new class implements RequestHandlerInterface {
            public $sharedData = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $session = $request->getAttribute('session');
                $this->sharedData = $session->get('shared_data');
                return new Response(200);
            }
        };

        $middleware->process($requestB, $handlerB);

        // Assert that Request B did not leak and see data from Request A
        $this->assertNull($handlerB->sharedData, 'Request B should not leak data from Request A.');
    }
}
