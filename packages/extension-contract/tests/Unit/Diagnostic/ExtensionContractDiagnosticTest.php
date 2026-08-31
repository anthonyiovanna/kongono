<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExtensionContract\Diagnostic\ExtensionContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExtensionContractDiagnosticTest extends TestCase
{
    public function testCatalogIdsMetadataAndSafeAllowlistedSchemasAreExact(): void
    {
        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-EXTENSION-%04d', $ordinal), range(1201, 1207)),
            array_map(static fn(ExtensionContractDiagnostic $diagnostic): string => $diagnostic->value, ExtensionContractDiagnostic::cases()),
        );

        foreach (ExtensionContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            $keys = [];
            foreach ($schema->fields() as $field) {
                $keys[] = $field->key();
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertContains('violation', $keys);
            foreach (['path', 'digest', 'identifier', 'payload', 'class', 'source_text'] as $forbidden) {
                self::assertNotContains($forbidden, $keys);
            }
        }
    }
}
