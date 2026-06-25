<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\View;

use EmbegeQ\Nutrisi\View\Engines\EngineResolver;
use EmbegeQ\Nutrisi\View\Factory;
use EmbegeQ\Nutrisi\View\FileViewFinder;
use EmbegeQ\Nutrisi\View\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ViewFactoryTest extends TestCase
{
    #[Test]
    public function it_renders_plain_php_views(): void
    {
        $viewsPath = sys_get_temp_dir() . '/embegeq-view-tests-' . uniqid();
        mkdir($viewsPath, 0755, true);

        file_put_contents($viewsPath . '/hello.php', 'Hello <?= $name ?>');

        $filesystem = new Filesystem();
        $factory = new Factory(
            new EngineResolver(),
            new FileViewFinder($filesystem, [$viewsPath]),
        );

        $factory->getShared(); // ensure trait state initialized

        $resolver = new EngineResolver();
        $resolver->register('php', fn () => new \EmbegeQ\Nutrisi\View\Engines\PhpEngine($filesystem));

        $factory = new Factory($resolver, new FileViewFinder($filesystem, [$viewsPath]));

        $html = $factory->make('hello', ['name' => 'EmbegeQ'])->render();

        $this->assertSame('Hello EmbegeQ', $html);
    }
}
