<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit\Exception;

use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use Kongono\Persistence\Exception\CombinedPersistenceFailureException;
use Kongono\Persistence\Exception\ExceptionInterface;
use Kongono\Persistence\Exception\InvalidPersistenceStateException;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\Exception\PersistenceCleanupException;
use Kongono\Persistence\Exception\PersistenceException;
use Kongono\Persistence\Exception\PersistenceOperationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PersistenceExceptionTest extends TestCase
{
    public function testExceptionFamilyAndDiagnosticsAreExact(): void
    {
        $primary = new RuntimeException('synthetic-provider-secret-primary');
        $cleanup = new RuntimeException('synthetic-provider-secret-cleanup');
        $exceptions = [
            InvalidPersistenceValueException::validation(),
            InvalidPersistenceStateException::lifecycle(),
            PersistenceOperationException::failed($primary),
            PersistenceCleanupException::failed($cleanup),
            new CombinedPersistenceFailureException($primary, $cleanup),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertInstanceOf(PersistenceException::class, $exception);
            self::assertMatchesRegularExpression('/^KGN-PERSISTENCE-100[1-7]$/D', $exception->diagnosticId());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            self::assertNotSame('', $exception->diagnosticRemediation());
            $contextFields = $exception->diagnosticContext()->fields();
            self::assertCount(3, $contextFields);
            $contextText = implode(' ', array_map(
                static fn($field): string => sprintf('%s=%s', $field->key(), $field->value()),
                $contextFields,
            ));
            foreach (['synthetic-provider-secret-primary', 'synthetic-provider-secret-cleanup'] as $private) {
                self::assertStringNotContainsString($private, $exception->getMessage());
                self::assertStringNotContainsString($private, (string) $exception);
                self::assertStringNotContainsString($private, $contextText);
            }
        }

        self::assertSame(PersistenceDiagnostic::Validation->value, $exceptions[0]->diagnosticId());
        self::assertSame(PersistenceDiagnostic::LifecycleState->value, $exceptions[1]->diagnosticId());
        self::assertSame($primary, $exceptions[2]->getPrevious());
        self::assertSame($cleanup, $exceptions[3]->getPrevious());

        $combined = $exceptions[4];
        self::assertInstanceOf(CombinedPersistenceFailureException::class, $combined);
        self::assertSame($primary, $combined->primaryFailure());
        self::assertSame($cleanup, $combined->cleanupFailure());
        self::assertSame($primary, $combined->getPrevious());
        self::assertSame(PersistenceDiagnostic::CombinedFailure->value, $combined->diagnosticId());
    }

    public function testMarkerCatchCompatibilityIsPackageSpecific(): void
    {
        $caught = null;
        try {
            throw InvalidPersistenceValueException::validation();
        } catch (ExceptionInterface $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(InvalidPersistenceValueException::class, $caught);
    }
}
