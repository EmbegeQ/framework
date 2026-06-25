<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Http\Controllers;

use EmbegeQ\Nutrisi\Contracts\View\FactoryInterface;
use EmbegeQ\Nutrisi\Contracts\View\ViewInterface;

/**
 * Base controller with view rendering helpers.
 */
abstract class Controller
{
    public function __construct(
        protected FactoryInterface $views,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    protected function view(string $view, array $data = []): ViewInterface
    {
        return $this->views->make($view, $data);
    }
}
