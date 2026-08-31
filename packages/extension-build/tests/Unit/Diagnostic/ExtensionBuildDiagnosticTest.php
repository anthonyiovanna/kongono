<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExtensionBuild\Diagnostic\ExtensionBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExtensionBuildDiagnosticTest extends TestCase
{
    public function testDiagnosticVocabularyAndFixedMetadataAreExact(): void
    {
        self::assertSame([
            'InvalidBuildInput' => 'KGN-EXTENSION-1208',
            'InvalidArtifact' => 'KGN-EXTENSION-1209',
            'StaleArtifact' => 'KGN-EXTENSION-1210',
            'PublicationFailed' => 'KGN-EXTENSION-1211',
            'BuildLimitExceeded' => 'KGN-EXTENSION-1212',
        ], array_column(ExtensionBuildDiagnostic::cases(), 'value', 'name'));

        foreach (ExtensionBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $context = $diagnostic->context(['violation' => 'invalid_shape', 'kind' => 'artifact']);
            self::assertSame(['violation', 'kind'], array_map(static fn($field): string => $field->key(), $context->fields()));
            foreach ($context->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
