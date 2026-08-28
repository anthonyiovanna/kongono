<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryTimeAnchorTest extends TestCase
{
    public function testCheckedConversionUsesExactDelta(): void
    {
        $anchor = new OpenTelemetryTimeAnchor(100, 1_000);
        self::assertSame(100, $anchor->monotonicNanoseconds());
        self::assertSame(1_000, $anchor->epochNanoseconds());
        self::assertSame(1_000, $anchor->toEpochNanoseconds(100));
        self::assertSame(1_025, $anchor->toEpochNanoseconds(125));
    }

    public function testInvalidAnchorsAndConversionFailClosed(): void
    {
        foreach ([[-1, 1], [0, 0]] as [$monotonic, $epoch]) {
            try {
                new OpenTelemetryTimeAnchor($monotonic, $epoch);
                self::fail('Expected invalid anchor.');
            } catch (InvalidOpenTelemetryConfigurationException) {
            }
        }
        $anchor = new OpenTelemetryTimeAnchor(10, 20);
        try {
            $anchor->toEpochNanoseconds(9);
            self::fail('Expected negative delta rejection.');
        } catch (OpenTelemetryMappingException) {
        }
        $overflow = new OpenTelemetryTimeAnchor(0, PHP_INT_MAX);
        $this->expectException(OpenTelemetryMappingException::class);
        $overflow->toEpochNanoseconds(1);
    }
}
