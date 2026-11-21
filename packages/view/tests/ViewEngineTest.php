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

    public function testSupportsUnlessIssetJsonAndPhpDirectives(): void
    {
        file_put_contents(
            $this->tempDir . '/views/extras.kgn.html',
            <<<'HTML'
@unless ($disabled)
<span>Active</span>
@endunless
@isset($meta)
<script>const data = @json($meta);</script>
@endisset
@php $sum = 2 + 3; @endphp
<p>{{ $sum }}</p>
HTML,
        );

        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        $html = $engine->render('extras', ['disabled' => false, 'meta' => ['ok' => true]]);

        self::assertStringContainsString('<span>Active</span>', $html);
        self::assertStringContainsString('{"ok":true}', $html);
        self::assertStringContainsString('<p>5</p>', $html);
    }

    public function testSupportsAutoEscapedTranslationDirective(): void
    {
        file_put_contents(
            $this->tempDir . '/views/i18n.kgn.html',
            '<h1>@t("greeting", ["name" => $user])</h1><p>@t("greeting", ["name" => $user], "ja")</p>',
        );

        $messages = [
            'en' => ['greeting' => 'Hello, :name!'],
            'ja' => ['greeting' => 'こんにちは、:name！'],
        ];
        $engine = (new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache'))
            ->withTranslator(static function (string $key, array $params = [], ?string $locale = null) use ($messages): string {
                $loc = $locale ?? 'en';
                $tpl = $messages[$loc][$key] ?? $key;
                $map = [];
                foreach ($params as $k => $v) {
                    $map[':' . $k] = (string) $v;
                }

                return strtr($tpl, $map);
            });

        $html = $engine->render('i18n', ['user' => '<b>Ada</b>']);
        self::assertStringContainsString('<h1>Hello, &lt;b&gt;Ada&lt;/b&gt;!</h1>', $html);
        self::assertStringContainsString('<p>こんにちは、&lt;b&gt;Ada&lt;/b&gt;！</p>', $html);
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
