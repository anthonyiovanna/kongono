<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Internal;

use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Internal\LeaseBudgetMeter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LeaseBudgetMeterTest extends TestCase
{
    public function testInclusiveLimitIsAcceptedAndPlusOneFails(): void
    {
        $meter = new LeaseBudgetMeter(10, 20);
        $meter->observe(30);
        self::assertSame(10, $meter->elapsedMilliseconds());
        self::assertSame(10, $meter->maximumLifetimeMilliseconds());

        $this->expectException(InvalidCredentialLeaseRuntimeException::class);
        $meter->observe(31);
    }

    /** @return iterable<string, array{list<int>, string}> */
    public static function invalidObservations(): iterable
    {
        yield 'negative start' => [[-1], 'clock_negative'];
        yield 'negative later' => [[1, -1], 'clock_negative'];
        yield 'regression' => [[4, 3], 'clock_regression'];
    }

    /** @param list<int> $values */
    #[DataProvider('invalidObservations')]
    public function testInvalidClockEvidenceFailsClosed(array $values, string $violation): void
    {
        try {
            $initial = array_shift($values);
            self::assertIsInt($initial);
            $meter = new LeaseBudgetMeter(20, $initial);
            foreach ($values as $value) {
                $meter->observe($value);
            }
            self::fail('Invalid clock evidence must fail.');
        } catch (InvalidCredentialLeaseRuntimeException $failure) {
            self::assertSame($violation, $failure->violation());
        }
    }
}
