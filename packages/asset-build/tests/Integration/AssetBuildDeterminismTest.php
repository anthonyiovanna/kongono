<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Integration;

use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class AssetBuildDeterminismTest extends TestCase
{
    public function testRepeatedAndReorderedBuildsAreByteIdentical(): void
    {
        self::assertSame(BuildProbe::run(false), BuildProbe::run(true));
    }

    public function testFreshProcessesAreStableForOriginalAndReorderedInputs(): void
    {
        $probe = dirname(__DIR__) . '/Fixture/BuildProbe.php';
        $first = self::process([$probe]);
        $second = self::process([$probe]);
        $reverse = self::process([$probe, '--reverse']);
        self::assertSame($first, $second);
        self::assertSame($first, $reverse);
        self::assertSame(BuildProbe::run(), json_decode($first, true, 64, JSON_THROW_ON_ERROR));
    }

    public function testSequentialFiberAndIndependentBuilderCallsRetainNoState(): void
    {
        $expected = BuildProbe::run();
        $first = new \Fiber(static fn(): array => BuildProbe::run(false));
        $second = new \Fiber(static fn(): array => BuildProbe::run(true));
        $first->start();
        $second->start();
        self::assertSame($expected, $first->getReturn());
        self::assertSame($expected, $second->getReturn());
        self::assertSame([], (new \ReflectionClass(\Kongono\AssetBuild\Build\NativeAssetBuilder::class))->getProperties());
    }

    /** @param list<string> $arguments */
    private static function process(array $arguments): string
    {
        $pipes = [];
        $process = proc_open([PHP_BINARY, ...$arguments], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), is_string($stderr) ? $stderr : 'fresh process failed');
        self::assertIsString($stdout);

        return trim($stdout);
    }
}
