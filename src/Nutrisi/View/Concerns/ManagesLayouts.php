<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\View\Concerns;

use EmbegeQ\Nutrisi\Contracts\View\ViewInterface;
use InvalidArgumentException;

/**
 * Manages Blade layout sections without static request state.
 */
trait ManagesLayouts
{
    /** @var array<string, string> */
    protected array $sections = [];

    /** @var array<int, string> */
    protected array $sectionStack = [];

    /** @var array<string, string> */
    protected array $parentPlaceholders = [];

    public function startSection(string $section, ?string $content = null): void
    {
        if ($content === null) {
            ob_start();
            $this->sectionStack[] = $section;

            return;
        }

        $this->extendSection($section, $content);
    }

    public function stopSection(bool $overwrite = false): string
    {
        if ($this->sectionStack === []) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->sectionStack);
        $contents = ob_get_clean();

        if ($contents === false) {
            $contents = '';
        }

        if ($overwrite) {
            $this->sections[$last] = $contents;
        } else {
            $this->extendSection($last, $contents);
        }

        return $last;
    }

    public function yieldSection(): string
    {
        if ($this->sectionStack === []) {
            return '';
        }

        return $this->yieldContent($this->stopSection());
    }

    protected function extendSection(string $section, string $content): void
    {
        if (isset($this->sections[$section])) {
            $this->sections[$section] .= $content;
        } else {
            $this->sections[$section] = $content;
        }
    }

    public function yieldContent(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    public function parentPlaceholder(string $section = ''): string
    {
        if (!isset($this->parentPlaceholders[$section])) {
            $this->parentPlaceholders[$section] = '__parent_' . bin2hex(random_bytes(8)) . '__';
        }

        return $this->parentPlaceholders[$section];
    }

    public function flushSections(): void
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->parentPlaceholders = [];
    }

    public function startPush(string $section, ?string $content = null): void
    {
        $this->startSection($section, $content);
    }

    public function stopPush(): void
    {
        $this->stopSection();
    }
}
