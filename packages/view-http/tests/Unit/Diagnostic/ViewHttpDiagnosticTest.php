<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ViewHttp\Diagnostic\ViewHttpDiagnostic;
use PHPUnit\Framework\TestCase;

final class ViewHttpDiagnosticTest extends TestCase
{
    public function testIdentifiersAreExactAndClosed(): void
    {
        self::assertSame(['KGN-VIEW-HTTP-1840', 'KGN-VIEW-HTTP-1841', 'KGN-VIEW-HTTP-1842', 'KGN-VIEW-HTTP-1843', 'KGN-VIEW-HTTP-1844'], array_map(static fn(ViewHttpDiagnostic $diagnostic): string => $diagnostic->value, ViewHttpDiagnostic::cases()));
    }

    public function testEveryDiagnosticHasBoundedPresentationText(): void
    {
        foreach (ViewHttpDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertLessThanOrEqual(160, strlen($diagnostic->title()));
            self::assertNotSame('', $diagnostic->remediation());
            self::assertLessThanOrEqual(256, strlen($diagnostic->remediation()));
            self::assertNotSame('', $diagnostic->contextSchema()->operation());
            self::assertNotSame('', $diagnostic->contextSchema()->violatedInvariant());
        }
    }

    public function testAllContextFieldsAreSafeAndAllowListed(): void
    {
        $expected = ['violation', 'limit', 'observed_count', 'status_code', 'header_count', 'output_byte_count'];
        foreach (ViewHttpDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame($expected, array_map(static fn($field): string => $field->key(), $fields));
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
