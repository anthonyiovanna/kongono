<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Exception;

use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\ExceptionInterface;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Model\FieldDefinition;
use Kongono\PersistenceBuild\Model\FieldRole;
use Kongono\PersistenceBuild\Model\FieldValueKind;
use PHPUnit\Framework\TestCase;

final class PersistenceBuildExceptionTest extends TestCase
{
    public function testExceptionFamilyUsesFixedNonDisclosingPresentation(): void
    {
        $private = 'C:/private/customer/password=correct-horse';
        $failure = InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourceJson);

        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-PERSISTENCE-BUILD-1001: Invalid persistence source', (string) $failure);
        self::assertStringNotContainsString($private, (string) $failure);
        foreach ($failure->diagnosticContext()->fields() as $field) {
            self::assertStringNotContainsString($private, (string) $field->value());
        }
    }

    public function testDirectConstructorFailureUsesFixedNonDisclosingPresentation(): void
    {
        $private = 'C:/private/customer/password=correct-horse';

        try {
            new FieldDefinition($private, 'id', 'id', 'id', FieldRole::Identity, FieldValueKind::Integer, false);
            self::fail('Expected direct constructor rejection.');
        } catch (InvalidPersistenceArtifactException $failure) {
            self::assertSame('KGN-PERSISTENCE-BUILD-1007: Invalid persistence artifact', (string) $failure);
            self::assertStringNotContainsString($private, (string) $failure);
            foreach ($failure->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($private, (string) $field->value());
            }
        }
    }
}
