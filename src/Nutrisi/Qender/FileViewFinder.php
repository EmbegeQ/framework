<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender;

use RuntimeException;

class FileViewFinder implements ViewFinderInterface
{
    /** @var string[] */
    private array $paths;

    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
    }

    public function find(string $view): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.q.php';

        foreach ($this->paths as $base) {
            $candidate = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("View not found: {$view}");
    }
}
