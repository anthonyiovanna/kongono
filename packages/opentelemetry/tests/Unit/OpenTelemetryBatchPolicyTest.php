<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryBatchPolicyTest extends TestCase
{
    public function testBoundaryValuesAreRetainedExactly(): void
    {
        $small = new OpenTelemetryBatchPolicy(1, 1, 1);
        $large = new OpenTelemetryBatchPolicy(1024, 256, 30_000);
        self::assertSame([1, 1, 1], [$small->queueCapacity(), $small->maximumBatchSize(), $small->transportTimeoutMilliseconds()]);
        self::assertSame([1024, 256, 30_000], [$large->queueCapacity(), $large->maximumBatchSize(), $large->transportTimeoutMilliseconds()]);
    }

    /** @return iterable<string, array{int, int, int}> */
    public static function invalidPolicies(): iterable
    {
        yield 'zero capacity' => [0, 1, 1];
        yield 'capacity too large' => [1025, 1, 1];
        yield 'zero batch' => [1, 0, 1];
        yield 'batch too large' => [1024, 257, 1];
        yield 'batch exceeds capacity' => [1, 2, 1];
        yield 'zero timeout' => [1, 1, 0];
        yield 'timeout too large' => [1, 1, 30_001];
    }

    #[DataProvider('invalidPolicies')]
    public function testInvalidPolicyFailsClosed(int $capacity, int $batch, int $timeout): void
    {
        $this->expectException(InvalidOpenTelemetryConfigurationException::class);
        new OpenTelemetryBatchPolicy($capacity, $batch, $timeout);
    }
}
