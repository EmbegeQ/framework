<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Foundation;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Foundation\ApplicationInterface;
use EmbegeQ\Nutrisi\Foundation\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Application kernel.
 *
 * Verifies base bindings, service provider registration/boot lifecycle,
 * path resolution, and environment detection.
 */
class ApplicationTest extends TestCase
{
    #[Test]
    public function it_registers_base_bindings_on_construction(): void
    {
        $app = new Application(__DIR__);

        $this->assertSame($app, $app->make('app'));
        $this->assertSame($app, $app->make(ContainerInterface::class));
        $this->assertSame($app, $app->make(ApplicationInterface::class));
    }

    #[Test]
    public function it_returns_version(): void
    {
        $app = new Application(__DIR__);

        $this->assertSame('0.1.0-dev', $app->version());
    }

    #[Test]
    public function it_resolves_base_path_correctly(): void
    {
        $app = new Application('/srv/app');

        $this->assertSame('/srv/app', $app->basePath());
        $this->assertStringEndsWith('subdir', $app->basePath('subdir'));
    }

    #[Test]
    public function it_resolves_config_path(): void
    {
        $app = new Application('/srv/app');

        $this->assertStringContainsString('config', $app->configPath());
    }

    #[Test]
    public function it_registers_and_boots_service_providers(): void
    {
        $app = new Application(__DIR__);

        $registered = false;
        $booted = false;

        $provider = new class ($registered, $booted) implements ServiceProviderInterface {
            public function __construct(
                private bool &$registered,
                private bool &$booted,
            ) {
            }

            public function register(ContainerInterface $app): void
            {
                $this->registered = true;
            }

            public function boot(ContainerInterface $app): void
            {
                $this->booted = true;
            }
        };

        $app->register($provider);

        $this->assertTrue($registered, 'Provider should be registered immediately.');
        $this->assertFalse($booted, 'Provider should NOT be booted before app->boot().');

        $app->boot();

        $this->assertTrue($booted, 'Provider should be booted after app->boot().');
        $this->assertTrue($app->isBooted());
    }

    #[Test]
    public function it_prevents_double_registration_of_same_provider(): void
    {
        $app = new Application(__DIR__);

        $callCount = 0;

        $provider = new class ($callCount) implements ServiceProviderInterface {
            public function __construct(private int &$count)
            {
            }

            public function register(ContainerInterface $app): void
            {
                $this->count++;
            }

            public function boot(ContainerInterface $app): void
            {
            }
        };

        $app->register($provider);
        $app->register($provider); // Same instance, should be skipped.

        $this->assertSame(1, $callCount, 'Provider register() should only be called once.');
    }

    #[Test]
    public function it_boots_late_registered_providers_immediately_when_already_booted(): void
    {
        $app = new Application(__DIR__);
        $app->boot(); // Boot first.

        $booted = false;

        $provider = new class ($booted) implements ServiceProviderInterface {
            public function __construct(private bool &$booted)
            {
            }

            public function register(ContainerInterface $app): void
            {
            }

            public function boot(ContainerInterface $app): void
            {
                $this->booted = true;
            }
        };

        $app->register($provider);

        $this->assertTrue($booted, 'Late-registered provider should be booted immediately.');
    }

    #[Test]
    public function it_defaults_to_production_environment(): void
    {
        $app = new Application(__DIR__);

        $this->assertSame('production', $app->environment());
    }

    #[Test]
    public function it_reads_environment_from_config(): void
    {
        $app = new Application(__DIR__);

        $config = new \EmbegeQ\Nutrisi\Config\Repository([
            'app' => ['env' => 'testing'],
        ]);

        $app->instance(RepositoryInterface::class, $config);

        $this->assertSame('testing', $app->environment());
    }

    #[Test]
    public function it_loads_dotenv_variables_on_construction(): void
    {
        $tempDir = __DIR__ . '/temp_test_env';
        if (!is_dir($tempDir)) {
            mkdir($tempDir);
        }

        file_put_contents($tempDir . '/.env', "TEST_FOO=bar\n");

        try {
            new Application($tempDir);
            $this->assertSame('bar', $_ENV['TEST_FOO'] ?? null);
        } finally {
            // Clean up
            if (file_exists($tempDir . '/.env')) {
                unlink($tempDir . '/.env');
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
            unset($_ENV['TEST_FOO']);
        }
    }
}
