<?php

declare(strict_types=1);

namespace Kongono\View\Tests;

use Kongono\View\HtmlSanitizer;
use Kongono\View\ViewEngine;
use Kongono\View\ViewException;
use Kongono\View\ViteManifest;
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
        self::assertSame(1, $engine->warmCache());
    }

    public function testViteManifestAndViteDirectiveSupportDevHmrAndProductionBuilds(): void
    {
        $publicDir = $this->tempDir . DIRECTORY_SEPARATOR . 'public';
        mkdir($publicDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . '.vite', 0o777, true);
        file_put_contents(
            $publicDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . 'manifest.json',
            json_encode([
                'resources/js/app.js' => [
                    'file' => 'assets/app-abc123.js',
                    'css' => ['assets/app-abc123.css'],
                    'imports' => ['_vendor.js'],
                ],
                '_vendor.js' => [
                    'file' => 'assets/vendor-def456.js',
                ],
            ], JSON_THROW_ON_ERROR),
        );
        file_put_contents(
            $this->tempDir . '/views/vite.kgn.html',
            '<head>@vite("resources/js/app.js")</head>',
        );

        $vite = new ViteManifest($publicDir);
        $engine = (new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache'))->withVite($vite);

        // Production mode emits hashed CSS, modulepreload, and module script
        $prodHtml = $engine->render('vite');
        self::assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-abc123.css">', $prodHtml);
        self::assertStringContainsString('<link rel="modulepreload" href="/build/assets/vendor-def456.js">', $prodHtml);
        self::assertStringContainsString('<script type="module" src="/build/assets/app-abc123.js"></script>', $prodHtml);
        self::assertSame('/build/assets/app-abc123.js', $vite->asset('resources/js/app.js'));

        // Dev HMR mode when public/hot exists
        file_put_contents($publicDir . DIRECTORY_SEPARATOR . 'hot', "http://127.0.0.1:5173\n");
        $devHtml = $engine->render('vite');
        self::assertStringContainsString('<script type="module" src="http://127.0.0.1:5173/@vite/client"></script>', $devHtml);
        self::assertStringContainsString('<script type="module" src="http://127.0.0.1:5173/resources/js/app.js"></script>', $devHtml);
    }

    public function testAutoReloadEnabledRecompilesModifiedTemplates(): void
    {
        $templatePath = $this->tempDir . '/views/live.kgn.html';
        file_put_contents($templatePath, '<p>v1: {{ $name }}</p>');

        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        self::assertTrue($engine->isAutoReload());
        self::assertSame('<p>v1: Ada</p>', $engine->render('live', ['name' => 'Ada']));

        file_put_contents($templatePath, '<p>v2: {{ $name }}</p>');
        touch($templatePath, time() + 10);

        self::assertSame('<p>v2: Ada</p>', $engine->render('live', ['name' => 'Ada']));
    }

    public function testAutoReloadDisabledCachesCompiledClosuresForPersistentWorkers(): void
    {
        $templatePath = $this->tempDir . '/views/worker.kgn.html';
        file_put_contents($templatePath, '<h1>Hello {{ $name }}</h1>');

        $devEngine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        $prodEngine = $devEngine->withAutoReload(false)->withTranslator(static fn(string $key): string => $key);
        self::assertTrue($devEngine->isAutoReload());
        self::assertFalse($prodEngine->isAutoReload());

        self::assertSame('<h1>Hello Ada</h1>', $prodEngine->render('worker', ['name' => 'Ada']));

        // Delete the source template after first render; cached closure must continue serving dynamic data
        unlink($templatePath);

        self::assertSame('<h1>Hello Grace</h1>', $prodEngine->render('worker', ['name' => 'Grace']));
        self::assertSame('<h1>Hello Alan</h1>', $prodEngine->render('/worker', ['name' => 'Alan']));

        // Invalid or traversal template names must still be rejected in production mode
        $this->expectException(ViewException::class);
        $prodEngine->render('../secret');
    }

    public function testWarmCachePreloadsClosuresWhenAutoReloadDisabled(): void
    {
        $templatePath = $this->tempDir . '/views/warm.kgn.html';
        file_put_contents($templatePath, '<span>{{ $label }}</span>');

        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache', autoReload: false);
        self::assertSame(1, $engine->warmCache());

        // Removing the source file before the first render proves warmCache() preloaded the closure
        unlink($templatePath);

        self::assertSame('<span>Preloaded</span>', $engine->render('warm', ['label' => 'Preloaded']));
    }

    public function testLiveScriptDirectiveCompilesDefaultAndCustomPaths(): void
    {
        file_put_contents($this->tempDir . '/views/live-default.kgn.html', '<head>@liveScript</head>');
        file_put_contents($this->tempDir . '/views/live-custom.kgn.html', '<head>@liveScript("/assets/kgn-live.js")</head>');

        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        self::assertSame('<head><script src="/_kongono/live.js?v=2.5.0" defer></script></head>', $engine->render('live-default'));
        self::assertSame('<head><script src="/assets/kgn-live.js" defer></script></head>', $engine->render('live-custom'));

        // Poisoned public/hot with javascript: scheme fails closed
        $pub = $this->tempDir . '/public_hot_sec';
        mkdir($pub, 0o777, true);
        file_put_contents($pub . '/hot', "javascript:alert(1)\n");
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Unsafe Vite hot-server URL');
        (new ViteManifest($pub))->tags('resources/js/app.js');
    }

    public function testRound3InlineSectionAndYieldDefaultAutoEscapeHtml(): void
    {
        file_put_contents(
            $this->tempDir . '/views/layouts/xss.kgn.html',
            '<title>@yield("title", $fallback)</title><div>@yield("missing", $fallback)</div>',
        );
        file_put_contents(
            $this->tempDir . '/views/xss-page.kgn.html',
            "@extends('layouts/xss')\n@section('title', \$pageTitle)\n",
        );

        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        $html = $engine->render('xss-page', [
            'pageTitle' => '</title><script>alert(1)</script>',
            'fallback' => '<img src=x onerror=alert(2)>',
        ]);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringContainsString('&lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $html);
    }

    public function testRound4ViteManifestEmptyBuildDirDoesNotEmitProtocolRelativeUrl(): void
    {
        $pub = $this->tempDir . '/public_empty_build';
        mkdir($pub . '/.vite', 0o777, true);
        file_put_contents($pub . '/.vite/manifest.json', json_encode([
            'app.js' => ['file' => 'assets/app-123.js'],
        ], JSON_THROW_ON_ERROR));

        $vite = new ViteManifest($pub, buildDirectory: '');
        self::assertSame('/assets/app-123.js', $vite->asset('app.js'));
        self::assertStringContainsString('src="/assets/app-123.js"', $vite->tags('app.js'));
        self::assertStringNotContainsString('//assets/', $vite->tags('app.js'));
    }

    public function testHtmlSanitizerAndSafeHtmlDirectiveBlockXssMxssAndPreserveRichProse(): void
    {
        $dirty = '<div class="code-wrap"><span class="code-lang">php</span><pre><code>echo 1;</code></pre></div>'
            . '<p>Read <a href="https://kongono.dev" target="_blank" onclick="steal()">docs</a> '
            . 'and <a href="javascript:alert(1)">bad</a> '
            . 'and <a href="//evil.test/phish">proto-rel</a>.</p>'
            . '<script>alert("xss")</script>'
            . '<svg onload="alert(2)"><script>alert(3)</script></svg>'
            . '<math><mtext><style>body{display:none}</style></mtext></math>'
            . '<img src="https://kongono.dev/logo.png" alt="Logo" onerror="pwn()" width="120">'
            . '<img src="data:text/html,<script>alert(4)</script>">';

        $clean = HtmlSanitizer::sanitize($dirty);
        self::assertStringContainsString('<div class="code-wrap"><span class="code-lang">php</span><pre><code>echo 1;</code></pre></div>', $clean);
        self::assertStringContainsString('<a href="https://kongono.dev" target="_blank" rel="noopener noreferrer">docs</a>', $clean);
        self::assertStringContainsString('<img src="https://kongono.dev/logo.png" alt="Logo" width="120">', $clean);
        self::assertStringNotContainsString('<script', $clean);
        self::assertStringNotContainsString('<svg', $clean);
        self::assertStringNotContainsString('<math', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
        self::assertStringNotContainsString('data:', $clean);
        self::assertStringNotContainsString('//evil.test', $clean);
        self::assertStringNotContainsString('onclick', $clean);
        self::assertStringNotContainsString('onerror', $clean);

        // @safeHtml directive renders allowlisted HTML safely inside .kgn.html templates
        file_put_contents($this->tempDir . '/views/prose.kgn.html', '<article>@safeHtml($body)</article>');
        $engine = new ViewEngine($this->tempDir . '/views', $this->tempDir . '/cache');
        $rendered = $engine->render('prose', ['body' => '<p><strong>Safe</strong></p><script>evil()</script>']);
        self::assertSame('<article><p><strong>Safe</strong></p></article>', $rendered);

        // @state(...), @island(...), and @bridgeScript directives emit strict CSP-safe attributes & script tags
        file_put_contents($this->tempDir . '/views/island.kgn.html', "<div @state(\$init)></div>\n@island('Charts/BidHistory', \$chartProps, 'visible', 'chart-1')\n@bridgeScript");
        $islandHtml = $engine->render('island', [
            'init' => ['open' => false, 'label' => 'A & "B" </script>'],
            'chartProps' => ['lotId' => 42, 'label' => 'Price </script>'],
        ]);
        self::assertStringContainsString('k-state="', $islandHtml);
        self::assertStringContainsString('id="chart-1" data-k-island="Charts/BidHistory"', $islandHtml);
        self::assertStringContainsString('data-k-hydrate="visible" k-ignore=""', $islandHtml);
        self::assertStringNotContainsString('</script>', substr($islandHtml, 0, strpos($islandHtml, '<script src="/_kongono/bridge.js?v=1.10.1"') ?: 0));
        self::assertStringContainsString('<script src="/_kongono/bridge.js?v=1.10.1" defer></script>', $islandHtml);

        // @island rejects traversal component names & invalid hydration strategies
        file_put_contents($this->tempDir . '/views/bad_island.kgn.html', "@island('../Evil', [])");
        try {
            $engine->render('bad_island');
            self::fail('Expected traversal @island name to be rejected.');
        } catch (ViewException $e) {
            self::assertStringContainsString('Invalid Bridge island component name', $e->getMessage());
        }
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
