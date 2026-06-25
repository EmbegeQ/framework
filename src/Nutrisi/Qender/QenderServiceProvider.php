<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender;

use EmbegeQ\Nutrisi\Qender\Implementations\FileQenderCompiler;
use EmbegeQ\Nutrisi\Qender\Implementations\QenderRenderer;

class QenderServiceProvider
{
    /**
     * Register minimal services into a container-like array/object.
     * This is a lightweight stub — container integration varies by app.
     *
     * @param array|object $container
     */
    public static function register(&$container, array $options = [])
    {
        $compiled = $options['compiled_path'] ?? __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'qender' . DIRECTORY_SEPARATOR . 'compiled';

        $viewPaths = $options['view_paths'] ?? [getcwd() . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'];

        // Simple container binding pattern
        if (is_array($container)) {
            $container['qender.compiler'] = new FileQenderCompiler($compiled);
            $container['qender.viewfinder'] = new FileViewFinder($viewPaths);
            $container['qender.renderer'] = new QenderRenderer($container['qender.viewfinder'], $container['qender.compiler']);
        }
    }
}
