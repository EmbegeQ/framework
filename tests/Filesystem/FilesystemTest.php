<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Filesystem;

use EmbegeQ\Nutrisi\Config\Repository as ConfigRepository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Filesystem\FilesystemInterface;
use EmbegeQ\Nutrisi\Filesystem\FilesystemManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    protected string $tempStorageDir;

    protected function setUp(): void
    {
        $this->tempStorageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'embegeq_storage_' . uniqid();
        @mkdir($this->tempStorageDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempStorageDir);
    }

    protected function deleteDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    $this->deleteDirectory($filePath);
                } else {
                    @unlink($filePath);
                }
            }
            @rmdir($dir);
        }
    }

    #[Test]
    public function local_disk_reads_and_writes_files(): void
    {
        $app = new ApplicationContainer();
        $config = new ConfigRepository([
            'filesystems' => [
                'default' => 'local',
                'disks' => [
                    'local' => [
                        'driver' => 'local',
                        'root' => $this->tempStorageDir,
                    ],
                ],
            ],
        ]);
        $app->instance(RepositoryInterface::class, $config);
        $app->alias(RepositoryInterface::class, 'config');

        $manager = new FilesystemManager($app);
        $disk = $manager->disk();

        $this->assertInstanceOf(FilesystemInterface::class, $disk);

        $disk->write('hello.txt', 'Hello Flysystem!');
        $this->assertTrue($disk->fileExists('hello.txt'));
        $this->assertSame('Hello Flysystem!', $disk->read('hello.txt'));

        $disk->delete('hello.txt');
        $this->assertFalse($disk->fileExists('hello.txt'));
    }
}
