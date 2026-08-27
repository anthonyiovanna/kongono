<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit\Diagnostic;

use Kongono\Authorization\Diagnostic\AuthorizationDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class AuthorizationDiagnosticTest extends TestCase
{
    public function testIdentifiersCategoriesAndFreshSafeContextsAreExact(): void
    {
        self::assertSame([
            'KGN-AUTHORIZATION-1001',
            'KGN-AUTHORIZATION-1002',
            'KGN-AUTHORIZATION-1003',
            'KGN-AUTHORIZATION-1004',
            'KGN-AUTHORIZATION-1005',
            'KGN-AUTHORIZATION-1006',
        ], array_column(AuthorizationDiagnostic::cases(), 'value'));
        self::assertSame([
            'validation',
            'registration',
            'policy_evidence',
            'currentness',
            'evaluation',
            'receipt',
        ], array_map(static fn(AuthorizationDiagnostic $diagnostic): string => $diagnostic->category(), AuthorizationDiagnostic::cases()));

        foreach (AuthorizationDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $first = $diagnostic->context();
            $second = $diagnostic->context();
            self::assertNotSame($first, $second);
            self::assertSame(
                ['package', 'failure_category', 'remediation'],
                array_map(static fn($field): string => $field->key(), $first->fields()),
            );
            self::assertSame('authorization', $first->fields()[0]->value());
            self::assertSame($diagnostic->category(), $first->fields()[1]->value());
            foreach ($first->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
