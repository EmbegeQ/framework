<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender\Implementations;

use EmbegeQ\Nutrisi\Qender\QenderCompilerInterface;
use RuntimeException;

class FileQenderCompiler implements QenderCompilerInterface
{
    private string $compiledPath;

    public function __construct(string $compiledPath)
    {
        $this->compiledPath = rtrim($compiledPath, DIRECTORY_SEPARATOR);
    }

    public function compile(string $path): string
    {
        if (!is_file($path)) {
            throw new RuntimeException("Template not found: {$path}");
        }

        if (!is_dir($this->compiledPath)) {
            @mkdir($this->compiledPath, 0775, true);
        }

        $hash = hash('sha256', $path . '|' . filemtime($path));
        $out = $this->compiledPath . DIRECTORY_SEPARATOR . $hash . '.php';

        $source = file_get_contents($path);
        $wrapped = "<?php\ndeclare(strict_types=1);\n/* Compiled by Qender */\n?>\n" . $source;

        file_put_contents($out, $wrapped);

        return $out;
    }
}
