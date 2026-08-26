<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Process;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\Process\NativeKongoEntrypoint;
use PHPUnit\Framework\TestCase;

final class NativeKongoEntrypointTest extends TestCase
{
    public function testExplicitStreamsReceiveExactlyOneSelectedDocument(): void
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);
        $entrypoint = new NativeKongoEntrypoint((new KongoApplicationFactory())->create());

        self::assertSame(0, $entrypoint->run(['--json'], $stdout, $stderr));
        self::assertSame(0, fseek($stdout, 0));
        self::assertSame(0, fseek($stderr, 0));
        self::assertStringContainsString('kongono.command-help', (string) stream_get_contents($stdout));
        self::assertSame('', stream_get_contents($stderr));
        self::assertTrue(fclose($stdout));
        self::assertTrue(fclose($stderr));
    }

    public function testOutputFailureReturnsInfrastructureWithoutFallbackWrite(): void
    {
        $unwritable = fopen('php://memory', 'rb');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($unwritable);
        self::assertIsResource($stderr);

        $status = (new NativeKongoEntrypoint((new KongoApplicationFactory())->create()))->run([], $unwritable, $stderr);
        self::assertSame(6, $status);
        self::assertSame(0, fseek($stderr, 0));
        self::assertSame('', stream_get_contents($stderr));
        self::assertTrue(fclose($unwritable));
        self::assertTrue(fclose($stderr));
    }
}
