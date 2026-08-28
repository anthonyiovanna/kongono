<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit\Exception;

use Kongono\OpenTelemetry\Exception\ExceptionInterface;
use Kongono\OpenTelemetry\Exception\OpenTelemetryExportException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenTelemetryExceptionTest extends TestCase
{
    public function testFixedStringificationAndExactCausesRemainSeparate(): void
    {
        $first = new RuntimeException('secret-one');
        $second = new RuntimeException('secret-two');
        $exception = OpenTelemetryExportException::combined([$first, $second], 2, 4);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($first, $exception->getPrevious());
        self::assertSame([$first, $second], $exception->failures());
        self::assertSame('KGN-OTEL-1004: OpenTelemetry transport export failed', (string) $exception);
        self::assertStringNotContainsString('secret', (string) $exception);
    }
}
