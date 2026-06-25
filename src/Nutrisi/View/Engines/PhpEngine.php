<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Engines;

use EmbegeQ\Nutrisi\Contracts\View\EngineInterface;
use EmbegeQ\Nutrisi\View\Filesystem;

/**
 * Plain PHP view engine.
 */
class PhpEngine implements EngineInterface
{
    public function __construct(protected Filesystem $files) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $path, array $data = []): string
    {
        ob_start();

        extract($data, EXTR_SKIP);

        try {
            include $path;
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        $contents = ob_get_clean();

        return $contents === false ? '' : $contents;
    }
}
