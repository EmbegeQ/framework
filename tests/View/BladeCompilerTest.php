<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\View;

use EmbegeQ\Nutrisi\View\Compilers\BladeCompiler;
use EmbegeQ\Nutrisi\View\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BladeCompilerTest extends TestCase
{
    private BladeCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new BladeCompiler(
            new Filesystem(),
            sys_get_temp_dir() . '/embegeq-blade-tests',
        );
    }

    #[Test]
    public function it_compiles_escaped_echoes(): void
    {
        $compiled = $this->compiler->compileString('Hello {{ $name }}');

        $this->assertStringContainsString('EmbegeQ\\Nutrisi\\View\\e($name)', $compiled);
    }

    #[Test]
    public function it_compiles_if_statements(): void
    {
        $compiled = $this->compiler->compileString('@if ($ready) yes @else no @endif');

        $this->assertStringContainsString('<?php if($ready): ?>', $compiled);
        $this->assertStringContainsString('<?php else: ?>', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    #[Test]
    public function it_compiles_layout_directives(): void
    {
        $compiled = $this->compiler->compileString("@extends('layouts.app')\n@section('content') body @endsection");

        $this->assertStringContainsString('$__env->make', $compiled);
        $this->assertStringContainsString('$__env->startSection', $compiled);
        $this->assertStringContainsString('$__env->stopSection', $compiled);
    }

    #[Test]
    public function it_compiles_vite_directive(): void
    {
        $compiled = $this->compiler->compileString("@vite(['resources/css/app.css'])");

        $this->assertStringContainsString('Foundation\\Vite::class', $compiled);
    }
}
