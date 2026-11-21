<?php

declare(strict_types=1);

namespace Kongono\View\Tests;

use Kongono\View\ViewEngine;
use Kongono\View\ViewException;
use PHPUnit\Framework\TestCase;

final class ViewEngineTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-view-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts', 0o777, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'components', 0o777, true);
        mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'cache', 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testCompilesLayoutsComponentsLoopsAndAutoEscapesHtml(): void
    {
        file_put_contents(
            $this->tempDir . '/views/layouts/base.kgn.html',
            '<html><title>@yield("title")</title><body>@yield("body")</body></html>',
        );
        file_put_contents(
            $this->tempDir . '/views/components/box.kgn.html',
            '<div class="box"><h2>{{ $heading }}</h2>{!! $slot !!}</div>',
        );
        file_put_contents(
            $this->tempDir . '/views/page.kgn.html',
            <<<'HTML'
@extends('layouts/base')
@section('title', 'Demo')
@section('body')
<x-box :heading="$heading">
@forelse ($items as $item)
<li>{{ $item }}</li>
@empty
<p>Nothing here</p>
@endforelse
</x-box>
@endsection
HTML,
        );

        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        $html = $engine->render('page', [
            'heading' => '<script>alert(1)</script>',
            'items' => ['Alpha', '<b>Beta</b>'],
        ]);

        self::assertStringContainsString('<title>Demo</title>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('<li>Alpha</li>', $html);
        self::assertStringContainsString('<li>&lt;b&gt;Beta&lt;/b&gt;</li>', $html);

        $emptyHtml = $engine->render('page', ['heading' => 'Empty', 'items' => []]);
        self::assertStringContainsString('<p>Nothing here</p>', $emptyHtml);

        self::assertSame(['components/box', 'layouts/base', 'page'], $engine->listTemplates());
    }

    public function testRejectsPathTraversal(): void
    {
        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        $this->expectException(ViewException::class);
        $engine->render('../secret');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = scandir($dir);
        if (false === $entries) {
            return;
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
