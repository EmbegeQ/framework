<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Filesystem;

use League\Flysystem\FilesystemOperator;

/**
 * Filesystem contract extending Flysystem's FilesystemOperator.
 */
interface FilesystemInterface extends FilesystemOperator
{
}
