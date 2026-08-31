<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Exception;

use Kongono\ExtensionBuild\Exception\ExtensionDeclarationPublicationException;
use Kongono\ExtensionBuild\Exception\InvalidExtensionBuildException;
use Kongono\ExtensionBuild\Exception\StaleExtensionDeclarationArtifactException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtensionBuildExceptionTest extends TestCase
{
    public function testExceptionMetadataContextAndStringificationAreBoundedAndDeterministic(): void
    {
        $previous = new RuntimeException('credential=secret private/path malformed-json digest-deadbeef');
        $exception = InvalidExtensionBuildException::artifact(
            'private/path',
            'credential=secret',
            -2,
            -3,
            -4,
            $previous,
        );

        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(0, $exception->getCode());
        self::assertSame('KGN-EXTENSION-1209', $exception->diagnosticId());
        self::assertSame(['unsupported_violation', 'unsupported_kind', 0, 0, 0], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
        foreach ([$exception->getMessage(), (string) $exception] as $presentation) {
            self::assertStringNotContainsString('credential=secret', $presentation);
            self::assertStringNotContainsString('private/path', $presentation);
            self::assertStringNotContainsString('malformed-json', $presentation);
            self::assertStringNotContainsString('deadbeef', $presentation);
        }
    }

    public function testEachConsumerExceptionUsesOnlyItsAssignedDiagnostic(): void
    {
        self::assertSame('KGN-EXTENSION-1208', InvalidExtensionBuildException::input('invalid_declaration_entry')->diagnosticId());
        self::assertSame('KGN-EXTENSION-1212', InvalidExtensionBuildException::limit('declaration_count', 'declaration', 257)->diagnosticId());
        self::assertSame('KGN-EXTENSION-1210', StaleExtensionDeclarationArtifactException::currentnessMismatch()->diagnosticId());
        self::assertSame('KGN-EXTENSION-1211', ExtensionDeclarationPublicationException::failed('existing_target_conflict')->diagnosticId());
    }
}
