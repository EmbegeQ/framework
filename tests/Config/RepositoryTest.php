<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Config;

use EmbegeQ\Nutrisi\Config\Repository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Config Repository.
 *
 * Verifies dot-notation access, default values, array manipulation,
 * and ArrayAccess implementation.
 */
class RepositoryTest extends TestCase
{
    #[Test]
    public function it_supports_dot_notation_get(): void
    {
        $config = new Repository([
            'database' => [
                'connections' => [
                    'mysql' => [
                        'host' => '127.0.0.1',
                        'port' => 3306,
                    ],
                ],
            ],
        ]);

        $this->assertSame('127.0.0.1', $config->get('database.connections.mysql.host'));
        $this->assertSame(3306, $config->get('database.connections.mysql.port'));
    }

    #[Test]
    public function it_supports_dot_notation_set(): void
    {
        $config = new Repository();

        $config->set('app.name', 'EmbegeQ');
        $config->set('app.debug', true);

        $this->assertSame('EmbegeQ', $config->get('app.name'));
        $this->assertTrue($config->get('app.debug'));
    }

    #[Test]
    public function it_returns_default_for_missing_keys(): void
    {
        $config = new Repository();

        $this->assertNull($config->get('nonexistent'));
        $this->assertSame('fallback', $config->get('nonexistent', 'fallback'));
    }

    #[Test]
    public function it_checks_key_existence_with_has(): void
    {
        $config = new Repository([
            'app' => ['name' => 'EmbegeQ'],
        ]);

        $this->assertTrue($config->has('app.name'));
        $this->assertFalse($config->has('app.version'));
        $this->assertFalse($config->has('missing.deep.key'));
    }

    #[Test]
    public function it_returns_all_items(): void
    {
        $items = ['app' => ['name' => 'EmbegeQ']];
        $config = new Repository($items);

        $this->assertSame($items, $config->all());
    }

    #[Test]
    public function it_supports_batch_set_with_array(): void
    {
        $config = new Repository();

        $config->set([
            'cache.driver' => 'redis',
            'cache.ttl' => 3600,
        ]);

        $this->assertSame('redis', $config->get('cache.driver'));
        $this->assertSame(3600, $config->get('cache.ttl'));
    }

    #[Test]
    public function it_supports_push_and_prepend(): void
    {
        $config = new Repository([
            'app' => ['providers' => ['AuthProvider']],
        ]);

        $config->push('app.providers', 'CacheProvider');
        $this->assertSame(['AuthProvider', 'CacheProvider'], $config->get('app.providers'));

        $config->prepend('app.providers', 'BootProvider');
        $this->assertSame(['BootProvider', 'AuthProvider', 'CacheProvider'], $config->get('app.providers'));
    }

    #[Test]
    public function it_supports_array_access(): void
    {
        $config = new Repository(['app' => ['name' => 'EmbegeQ']]);

        $this->assertTrue(isset($config['app.name']));
        $this->assertSame('EmbegeQ', $config['app.name']);

        $config['app.version'] = '0.1.0';
        $this->assertSame('0.1.0', $config['app.version']);

        unset($config['app.version']);
        $this->assertNull($config['app.version']);
    }

    #[Test]
    public function it_handles_null_values_correctly(): void
    {
        $config = new Repository([
            'app' => ['nullable' => null],
        ]);

        // A key that exists with a null value should be found by has().
        $this->assertTrue($config->has('app.nullable'));
        $this->assertNull($config->get('app.nullable'));

        // But a truly missing key returns the default.
        $this->assertSame('default', $config->get('app.missing', 'default'));
    }
}
