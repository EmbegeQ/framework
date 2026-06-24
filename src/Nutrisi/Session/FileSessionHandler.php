<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Session;

use SessionHandlerInterface;

/**
 * File-based session handler implementation.
 */
class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * Create a new FileSessionHandler instance.
     *
     * @param  string  $path  The directory path where session files are stored.
     * @param  int  $lifetime  The session lifetime in minutes.
     */
    public function __construct(
        protected string $path,
        protected int $lifetime = 120
    ) {}

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $name): bool
    {
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id): string|false
    {
        $file = $this->path . DIRECTORY_SEPARATOR . $id;

        if (file_exists($file)) {
            if (filemtime($file) + ($this->lifetime * 60) >= time()) {
                $content = @file_get_contents($file);
                return $content !== false ? $content : '';
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        $file = $this->path . DIRECTORY_SEPARATOR . $id;

        return @file_put_contents($file, $data, LOCK_EX) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        $file = $this->path . DIRECTORY_SEPARATOR . $id;

        if (file_exists($file)) {
            @unlink($file);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $max_lifetime): int|false
    {
        $count = 0;
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*');

        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) + $max_lifetime < time()) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
