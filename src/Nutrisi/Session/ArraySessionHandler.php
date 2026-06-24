<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Session;

use SessionHandlerInterface;

/**
 * In-memory session handler for testing purposes.
 */
class ArraySessionHandler implements SessionHandlerInterface
{
    /**
     * The array storing the sessions.
     *
     * @var array<string, string>
     */
    protected array $sessions = [];

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $name): bool
    {
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
        return $this->sessions[$id] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        $this->sessions[$id] = $data;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        unset($this->sessions[$id]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }
}
