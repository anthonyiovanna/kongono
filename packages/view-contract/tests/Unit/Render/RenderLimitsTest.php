<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Render;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Render\RenderLimits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RenderLimitsTest extends TestCase
{
    public function testMinimumAndMaximumLimitsAreAcceptedExactly(): void
    {
        $minimum = new RenderLimits(1, 1);
        self::assertSame(1, $minimum->maxOutputBytes());
        self::assertSame(1, $minimum->maxRenderSteps());

        $maximum = new RenderLimits(8388608, 1000000);
        self::assertSame(8388608, $maximum->maxOutputBytes());
        self::assertSame(1000000, $maximum->maxRenderSteps());
        self::assertTrue($maximum->equals(new RenderLimits(8388608, 1000000)));
        self::assertFalse($maximum->equals($minimum));
    }

    /** @return iterable<string, array{int, int}> */
    public static function invalidLimits(): iterable
    {
        yield 'output below' => [0, 1];
        yield 'output above' => [8388609, 1];
        yield 'steps below' => [1, 0];
        yield 'steps above' => [1, 1000001];
    }

    #[DataProvider('invalidLimits')]
    public function testOutOfRangeLimitsFailClosed(int $output, int $steps): void
    {
        $this->expectException(InvalidViewContractException::class);
        new RenderLimits($output, $steps);
    }
}
