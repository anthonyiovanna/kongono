<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Integration;

use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class ViewBuildDeterminismTest extends TestCase
{
    public function testFreshChildProducesTheSameFourIndependentIdentities(): void
    {
        $artifact = BuildProbe::artifact();
        $expected = json_encode([
            $artifact->sourceIdentity()->value(),
            $artifact->modelSha256(),
            $artifact->generatedPhpSha256(),
            $artifact->compiledViewIdentity()->value(),
        ], JSON_THROW_ON_ERROR) . "\n";
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/Fixture/BuildProbe.php');
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), (string) $stderr);
        self::assertSame($expected, $stdout);
    }
}
