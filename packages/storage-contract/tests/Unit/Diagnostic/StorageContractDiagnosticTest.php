<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\StorageContract\Diagnostic\StorageContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class StorageContractDiagnosticTest extends TestCase
{
    public function testDiagnosticIdsAndSafeFieldVocabularyAreExact(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-STORAGE-CONTRACT-' . $number, range(2101, 2108)), array_column(StorageContractDiagnostic::cases(), 'value'));
        foreach (StorageContractDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame(['violation', 'operation', 'outcome', 'capability', 'digest_algorithm', 'entry_count', 'entry_position', 'limit'], array_map(static fn($field): string => $field->key(), $fields));
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }
}
