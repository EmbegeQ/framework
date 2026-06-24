<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Session;

use EmbegeQ\Nutrisi\Contracts\Session\SessionInterface;
use SessionHandlerInterface;

/**
 * Stateful-safe Session Store implementation.
 */
class Store implements SessionInterface
{
    /**
     * The session ID.
     */
    protected string $id;

    /**
     * The session name.
     */
    protected string $name;

    /**
     * The session attributes.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * The session handler.
     */
    protected SessionHandlerInterface $handler;

    /**
     * Session started flag.
     */
    protected bool $started = false;

    /**
     * Create a new Store instance.
     */
    public function __construct(string $name, SessionHandlerInterface $handler, ?string $id = null)
    {
        $this->name = $name;
        $this->handler = $handler;
        $this->setId($id ?: $this->generateSessionId());
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id): void
    {
        if ($this->isValidId($id)) {
            $this->id = $id;
        } else {
            $this->id = $this->generateSessionId();
        }
    }

    /**
     * Check if the session ID is valid.
     */
    protected function isValidId(string $id): bool
    {
        return preg_match('/^[a-f0-9]{40}$/', $id) === 1;
    }

    /**
     * Generate a new session ID.
     */
    protected function generateSessionId(): string
    {
        return bin2hex(random_bytes(20));
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        $this->attributes = array_merge($this->attributes, $this->readFromHandler());

        if (!$this->has('_token')) {
            $this->regenerateToken();
        }

        $this->started = true;
        return true;
    }

    /**
     * Read the session data from the handler.
     *
     * @return array<string, mixed>
     */
    protected function readFromHandler(): array
    {
        $data = $this->handler->read($this->id);

        if (is_string($data) && $data !== '') {
            $decoded = @unserialize($data);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        $this->handler->write($this->id, serialize($this->attributes));
        $this->started = false;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes) && $this->attributes[$key] !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        $this->attributes = [];
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(): bool
    {
        $this->flush();
        return $this->regenerate(true);
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(bool $destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->id);
        }

        $this->setId($this->generateSessionId());
        $this->regenerateToken();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function token(): string
    {
        return (string) $this->get('_token', '');
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateToken(): void
    {
        $this->put('_token', bin2hex(random_bytes(20)));
    }
}
