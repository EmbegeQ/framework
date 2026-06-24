<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Log;

use EmbegeQ\Nutrisi\Config\Repository as ConfigRepository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Log\LogManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogTest extends TestCase
{
    protected string $tempLogDir;

    protected function setUp(): void
    {
        $this->tempLogDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'embegeq_logs_' . uniqid();
        @mkdir($this->tempLogDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempLogDir)) {
            $files = glob($this->tempLogDir . DIRECTORY_SEPARATOR . '*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->tempLogDir);
        }
    }

    #[Test]
    public function it_creates_default_single_logger(): void
    {
        $app = new ApplicationContainer();
        $logPath = $this->tempLogDir . DIRECTORY_SEPARATOR . 'test.log';
        $config = new ConfigRepository([
            'logging' => [
                'default' => 'single',
                'channels' => [
                    'single' => [
                        'driver' => 'single',
                        'path' => $logPath,
                        'level' => 'debug',
                    ],
                ],
            ],
        ]);
        $app->instance(RepositoryInterface::class, $config);
        $app->alias(RepositoryInterface::class, 'config');

        $manager = new LogManager($app);
        $logger = $manager->channel();

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $logger->info('Hello EmbegeQ Log!');

        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('Hello EmbegeQ Log!', $logContent);
        $this->assertStringContainsString('INFO', $logContent);
    }
}
