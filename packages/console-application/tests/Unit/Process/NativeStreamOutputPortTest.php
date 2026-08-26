<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Process;

use Kongono\ConsoleApplication\Exception\InvalidConsoleApplicationValueException;
use Kongono\ConsoleApplication\Process\NativeStreamOutputPort;
use PHPUnit\Framework\TestCase;

final class NativeStreamOutputPortTest extends TestCase
{
    public function testWritesCompleteBytesWithoutClosingCallerStream(): void
    {
        $stream = fopen('php://memory', 'w+b');
        self::assertIsResource($stream);
        $port = new NativeStreamOutputPort($stream);
        $port->write("complete\n");

        self::assertSame(0, fseek($stream, 0));
        self::assertSame("complete\n", stream_get_contents($stream));
        self::assertTrue(fclose($stream));
    }

    public function testRejectsInvalidAndUnwritableStreamsWithoutRetry(): void
    {
        try {
            (new \ReflectionClass(NativeStreamOutputPort::class))->newInstance('not-a-resource');
            self::fail('Invalid stream must be rejected.');
        } catch (InvalidConsoleApplicationValueException $failure) {
            self::assertSame('KGN-CONSOLE-APPLICATION-1003', $failure->diagnosticId());
        }

        $stream = fopen('php://memory', 'rb');
        self::assertIsResource($stream);
        $port = new NativeStreamOutputPort($stream);
        $this->expectException(InvalidConsoleApplicationValueException::class);
        try {
            $port->write("cannot-write\n");
        } finally {
            self::assertTrue(fclose($stream));
        }
    }
}
