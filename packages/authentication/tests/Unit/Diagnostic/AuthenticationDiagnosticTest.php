<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit\Diagnostic;

use Kongono\Authentication\Diagnostic\AuthenticationDiagnostic;
use PHPUnit\Framework\TestCase;

final class AuthenticationDiagnosticTest extends TestCase
{
    public function testDiagnosticIdentifiersCategoriesAndSafeContextAreExact(): void
    {
        self::assertSame([
            'KGN-AUTHENTICATION-1001',
            'KGN-AUTHENTICATION-1002',
            'KGN-AUTHENTICATION-1003',
            'KGN-AUTHENTICATION-1004',
            'KGN-AUTHENTICATION-1005',
            'KGN-AUTHENTICATION-1006',
            'KGN-AUTHENTICATION-1007',
            'KGN-AUTHENTICATION-1008',
        ], array_column(AuthenticationDiagnostic::cases(), 'value'));
        self::assertSame([
            'validation',
            'registration',
            'evidence',
            'verification',
            'freshness',
            'strength',
            'cleanup',
            'combined_failure',
        ], array_map(static fn(AuthenticationDiagnostic $diagnostic): string => $diagnostic->category(), AuthenticationDiagnostic::cases()));

        foreach (AuthenticationDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $context = $diagnostic->context();
            self::assertSame(
                ['package', 'failure_category', 'remediation'],
                array_map(static fn($field): string => $field->key(), $context->fields()),
            );
            self::assertSame('authentication', $context->fields()[0]->value());
            self::assertSame($diagnostic->category(), $context->fields()[1]->value());
        }
    }
}
