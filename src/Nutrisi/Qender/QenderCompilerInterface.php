<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender;

interface QenderCompilerInterface
{
    /**
     * Compile a template source path into a compiled PHP path.
     * Returns the full path to the compiled PHP file.
     */
    public function compile(string $path): string;
}
