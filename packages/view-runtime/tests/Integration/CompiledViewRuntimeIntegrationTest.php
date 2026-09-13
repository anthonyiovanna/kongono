<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Integration;

use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Render\CompiledViewRenderer;
use Kongono\ViewRuntime\Tests\Fixture\RuntimeProbe;
use PHPUnit\Framework\TestCase;

final class CompiledViewRuntimeIntegrationTest extends TestCase
{
    public function testRealBuilderPublisherLoaderAndRendererComposeWithoutArtifactMutation(): void
    {
        $fixture = RuntimeProbe::publish();
        $before = [hash_file('sha256', $fixture['directory'] . '/artifact.json'), hash_file('sha256', $fixture['directory'] . '/compiled.php')];
        try {
            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            $result = (new CompiledViewRenderer())->render($loaded, RuntimeProbe::listRequest($fixture['artifact'], 'a"<&\'', ['<one>', '&two;']));
            self::assertInstanceOf(RenderResult::class, $result);
            self::assertSame('<div title="a&quot;&lt;&amp;&apos;">&lt;one&gt;&amp;two;</div>', $result->output()->bytes());
            self::assertSame($before, [hash_file('sha256', $fixture['directory'] . '/artifact.json'), hash_file('sha256', $fixture['directory'] . '/compiled.php')]);
            $entries = scandir($fixture['directory']);
            self::assertIsArray($entries);
            self::assertSame(['artifact.json', 'compiled.php'], array_values(array_diff($entries, ['.', '..'])));
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testFreshChildLoadsAndRendersTheSameContract(): void
    {
        $script = dirname(__DIR__) . '/Fixture/RuntimeProbe.php';
        $process = proc_open([PHP_BINARY, $script], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, dirname(__DIR__, 4));
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string) $stderr);
        self::assertSame(json_encode(['type' => RenderResult::class, 'output' => '&lt;p&gt;&lt;&amp;&gt;&lt;/p&gt;'], JSON_THROW_ON_ERROR) . "\n", $stdout);
    }
}
