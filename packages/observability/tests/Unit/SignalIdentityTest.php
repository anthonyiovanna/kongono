<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\SignalIdentity;
use PHPUnit\Framework\TestCase;

final class SignalIdentityTest extends TestCase
{
    public function testNameVersionBoundariesEqualityAndNoNormalizationAreExact(): void
    {
        $first = new SignalIdentity('request.completed', 1);
        self::assertSame('request.completed', $first->name());
        self::assertSame(1, $first->schemaVersion());
        self::assertTrue($first->equals(new SignalIdentity('request.completed', 1)));
        self::assertFalse($first->equals(new SignalIdentity('request.completed', 2)));
        self::assertSame(65_535, (new SignalIdentity(str_repeat('a', 128), 65_535))->schemaVersion());

        foreach ([['', 1], ['Request', 1], ['request:event', 1], [str_repeat('a', 129), 1], ['request', 0], ['request', 65_536]] as [$name, $version]) {
            try {
                new SignalIdentity($name, $version);
                self::fail('Invalid signal identity was accepted.');
            } catch (InvalidObservabilityValueException $exception) {
                self::assertSame('KGN-OBSERVABILITY-1003', $exception->diagnosticId());
                if ('' !== $name) {
                    self::assertStringNotContainsString($name, $exception->getMessage());
                }
            }
        }
    }
}
