<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Diagnostic;

use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use PHPUnit\Framework\TestCase;

final class NetworkEgressRuntimeDiagnosticTest extends TestCase
{
    public function testExactClosedDiagnosticIdentityRangeIsPresent(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-EGRESS-RUNTIME-' . $number, range(1520, 1529)), array_column(NetworkEgressRuntimeDiagnostic::cases(), 'value'));
        foreach (NetworkEgressRuntimeDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertNotSame('', $diagnostic->context([])->operation());
        }
    }

    public function testArbitraryContextIsRejectedWithoutPresentation(): void
    {
        try {
            NetworkEgressRuntimeDiagnostic::TransportFailed->context(['stage' => 'secret.example.test']);
            self::fail('Expected arbitrary diagnostic context to be rejected.');
        } catch (InvalidEgressRuntimeException $failure) {
            self::assertStringNotContainsString('secret.example.test', $failure->getMessage());
            self::assertStringNotContainsString('secret.example.test', (string) $failure);
        }
    }
}
