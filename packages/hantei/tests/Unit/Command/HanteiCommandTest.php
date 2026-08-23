<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit\Command;

use Kongono\Hantei\Command\HanteiCommand;
use PHPUnit\Framework\TestCase;

final class HanteiCommandTest extends TestCase
{
    public function testInvalidUsageWritesOnlyCanonicalSafeStderr(): void
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $exit = (new HanteiCommand())->run(['repair', 'secret-value'], $stdout, $stderr);
        rewind($stdout);
        rewind($stderr);
        $out = stream_get_contents($stdout);
        $error = stream_get_contents($stderr);

        self::assertSame(64, $exit);
        self::assertSame('', $out);
        self::assertIsString($error);
        self::assertStringEndsWith("\n", $error);
        self::assertStringNotContainsString("\r", $error);
        self::assertStringNotContainsString('secret-value', $error);
        $decoded = json_decode($error, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame('KGN-HANTEI-1001', $decoded['diagnostic_id']);
    }

    public function testBlockedSemanticReportUsesExitTwoAndStdout(): void
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $exit = (new HanteiCommand())->run(['verify', __DIR__ . '/missing'], $stdout, $stderr);
        rewind($stdout);
        rewind($stderr);
        $out = stream_get_contents($stdout);
        $error = stream_get_contents($stderr);

        self::assertSame(2, $exit);
        self::assertIsString($out);
        $decoded = json_decode($out, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame('BLOCKED', $decoded['verdict']);
        self::assertSame('', $error);
    }
}
