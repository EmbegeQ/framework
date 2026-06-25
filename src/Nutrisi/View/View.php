<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View;

use EmbegeQ\Nutrisi\Contracts\View\EngineInterface;
use EmbegeQ\Nutrisi\Contracts\View\ViewInterface;

/**
 * Rendered view instance.
 */
class View implements ViewInterface, \Stringable
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected Factory $factory,
        protected EngineInterface $engine,
        protected string $view,
        protected string $path,
        protected array $data = [],
    ) {}

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->view;
    }

    /**
     * {@inheritdoc}
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): string
    {
        try {
            return $this->engine->get($this->path, $this->gatherData());
        } finally {
            $this->factory->flushSections();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toHtml(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @return array<string, mixed>
     */
    protected function gatherData(): array
    {
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data[$key] = $value->toArray();
            }
        }

        return $data;
    }
}
