<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Cache;

use EmbegeQ\Nutrisi\Cache\ArrayCacheStore;
use EmbegeQ\Nutrisi\Cache\CacheManager;
use EmbegeQ\Nutrisi\Cache\FileCacheStore;
use EmbegeQ\Nutrisi\Config\Repository as ConfigRepository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    protected string $tempCacheDir;

    protected function setUp(): void
    {
        $this->tempCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'embegeq_cache_' . uniqid();
        @mkdir($this->tempCacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempCacheDir);
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
    public function array_store_set_get_has_delete_clear(): void
    {
        $store = new ArrayCacheStore();

        $this->assertFalse($store->has('foo'));
        $this->assertNull($store->get('foo'));

        $this->assertTrue($store->set('foo', 'bar'));
        $this->assertTrue($store->has('foo'));
        $this->assertSame('bar', $store->get('foo'));

        $this->assertTrue($store->delete('foo'));
        $this->assertFalse($store->has('foo'));

        $store->set('a', 1);
        $store->set('b', 2);
        $store->clear();
        $this->assertFalse($store->has('a'));
        $this->assertFalse($store->has('b'));
    }

    #[Test]
    public function array_store_multiple_operations(): void
    {
        $store = new ArrayCacheStore();

        $store->setMultiple(['a' => 1, 'b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => null], (array) $store->getMultiple(['a', 'b', 'c']));

        $store->deleteMultiple(['a', 'b']);
        $this->assertFalse($store->has('a'));
        $this->assertFalse($store->has('b'));
    }

    #[Test]
    public function array_store_expiration(): void
    {
        $store = new ArrayCacheStore();

        $store->set('foo', 'bar', -10); // Expired 10 seconds ago
        $this->assertFalse($store->has('foo'));
        $this->assertNull($store->get('foo'));
    }

    #[Test]
    public function file_store_set_get_has_delete_clear(): void
    {
        $store = new FileCacheStore($this->tempCacheDir);

        $this->assertFalse($store->has('foo'));
        $this->assertTrue($store->set('foo', 'bar'));
        $this->assertTrue($store->has('foo'));
        $this->assertSame('bar', $store->get('foo'));

        $this->assertTrue($store->delete('foo'));
        $this->assertFalse($store->has('foo'));

        $store->set('a', 1);
        $store->set('b', 2);
        $store->clear();
        $this->assertFalse($store->has('a'));
        $this->assertFalse($store->has('b'));
    }

    #[Test]
    public function file_store_expiration(): void
    {
        $store = new FileCacheStore($this->tempCacheDir);

        $store->set('foo', 'bar', -5); // Expired 5 seconds ago
        $this->assertFalse($store->has('foo'));
        $this->assertNull($store->get('foo'));
    }

    #[Test]
    public function cache_manager_resolves_default_and_custom_stores(): void
    {
        $app = new ApplicationContainer();
        $config = new ConfigRepository([
            'cache' => [
                'default' => 'array',
            ],
        ]);
        $app->instance(RepositoryInterface::class, $config);
        $app->alias(RepositoryInterface::class, 'config');

        $manager = new CacheManager($app);

        $this->assertInstanceOf(ArrayCacheStore::class, $manager->store());
        $this->assertInstanceOf(ArrayCacheStore::class, $manager->driver('array'));
    }
}
