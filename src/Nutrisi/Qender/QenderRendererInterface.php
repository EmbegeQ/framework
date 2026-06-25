<?php
declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Qender;

interface QenderRendererInterface
{
    /**
     * Render a view by name with data and return the rendered string.
     *
     * @param string $view Dot-notated view name (e.g. "pages.home")
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string;
}
