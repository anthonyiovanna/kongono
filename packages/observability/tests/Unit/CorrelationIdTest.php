<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\CorrelationId;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use PHPUnit\Framework\TestCase;

final class CorrelationIdTest extends TestCase
{
    public function testOpaqueCorrelationBoundsEqualityAndNoNormalizationAreExact(): void
    {
        foreach (['a', ' Correlation-ID ', 'café', str_repeat('a', 128)] as $valid) {
            self::assertSame($valid, (new CorrelationId($valid))->value());
        }
        self::assertTrue((new CorrelationId('same'))->equals(new CorrelationId('same')));
        self::assertFalse((new CorrelationId('same'))->equals(new CorrelationId('Same')));

        foreach (['', str_repeat('a', 129), "\xC3\x28", "line\rbreak", "\u{009F}"] as $invalid) {
            try {
                new CorrelationId($invalid);
                self::fail('Invalid correlation ID was accepted.');
            } catch (InvalidObservabilityValueException $exception) {
                self::assertSame('KGN-OBSERVABILITY-1001', $exception->diagnosticId());
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, $exception->getMessage());
                    self::assertStringNotContainsString($invalid, (string) $exception);
                }
            }
        }
    }
}
