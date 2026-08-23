<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit\Diagnostic;

use InvalidArgumentException;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\Exception\InvalidHanteiValueException;
use PHPUnit\Framework\TestCase;

final class HanteiDiagnosticTest extends TestCase
{
    public function testIdentifiersAndSafeSchemasAreExact(): void
    {
        self::assertSame([
            'KGN-HANTEI-1001',
            'KGN-HANTEI-1002',
            'KGN-HANTEI-1003',
            'KGN-HANTEI-1004',
            'KGN-HANTEI-1005',
            'KGN-HANTEI-1006',
            'KGN-HANTEI-1007',
            'KGN-HANTEI-1008',
        ], array_map(static fn(HanteiDiagnostic $diagnostic): string => $diagnostic->value, HanteiDiagnostic::cases()));
        foreach (HanteiDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context(match ($diagnostic) {
                HanteiDiagnostic::InvalidRequest => 'invalid-command-usage',
                HanteiDiagnostic::InvalidProfile => 'profile-mismatch',
                HanteiDiagnostic::SubjectUnavailable => 'subject-unavailable',
                HanteiDiagnostic::SubjectChanged => 'subject-changed',
                HanteiDiagnostic::ToolUnavailable => 'tool-unavailable',
                HanteiDiagnostic::CheckBlocked => 'process-timeout',
                HanteiDiagnostic::InvalidEvidence => 'invalid-evidence',
                HanteiDiagnostic::UnexpectedCommandFailure => 'unexpected-failure',
            });
            self::assertSame(DiagnosticDisclosure::Safe, $context->fields()[0]->disclosure());
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }

    public function testExceptionStringificationDoesNotDisclosePreviousException(): void
    {
        $secret = 'secret-' . bin2hex(random_bytes(8));
        $exception = new InvalidHanteiRequestException(HanteiDiagnostic::InvalidRequest, 'invalid-root', new \RuntimeException($secret));
        self::assertStringNotContainsString($secret, (string) $exception);
        self::assertSame('KGN-HANTEI-1001: Use a valid read-only Hantei request', (string) $exception);

        $value = InvalidHanteiValueException::evidence();
        self::assertInstanceOf(InvalidArgumentException::class, $value);
        self::assertStringNotContainsString($secret, (string) $value);
    }
}
