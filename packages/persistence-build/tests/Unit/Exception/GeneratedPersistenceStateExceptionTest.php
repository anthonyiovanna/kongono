<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Exception;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\ExceptionInterface;
use Kongono\PersistenceBuild\Exception\GeneratedPersistenceStatePublicationException;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateArtifact;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GeneratedPersistenceStateExceptionTest extends TestCase
{
    public function testFiveGeneratedStateDiagnosticsAreUniqueActionableAndSafe(): void
    {
        $generated = array_slice(PersistenceBuildDiagnostic::cases(), 10, 5);
        self::assertCount(5, $generated);
        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-PERSISTENCE-BUILD-%04d', $ordinal), range(1011, 1015)),
            array_column($generated, 'value'),
        );
        foreach ($generated as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->context('generated_structure')->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }

    public function testExceptionPresentationAndArtifactConstructionAreNonDisclosingAndConsistent(): void
    {
        $private = 'C:/private/customer/token=correct-horse';
        $failure = InvalidGeneratedPersistenceStateException::value();
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-PERSISTENCE-BUILD-1015: Invalid generated persistence state value', (string) $failure);
        self::assertStringNotContainsString($private, (string) $failure);
        foreach ($failure->diagnosticContext()->fields() as $field) {
            self::assertStringNotContainsString($private, (string) $field->value());
        }

        $root = dirname(__DIR__, 5);
        $source = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $source);
        $artifact = (new GeneratedPersistenceStateGenerator())->generate($root, $source, $persistence->canonicalBytes());
        try {
            new GeneratedPersistenceStateArtifact(
                $artifact->canonicalBytes() . $private,
                $artifact->generatedFileSha256(),
                $artifact->persistenceArtifactSha256(),
                $artifact->persistenceModelSha256(),
                $artifact->persistenceBuilderImplementationSha256(),
                $artifact->persistenceGeneratorImplementationSha256(),
                $artifact->generatedNamespace(),
                $artifact->classMap(),
            );
            self::fail('Expected direct artifact identity rejection.');
        } catch (InvalidGeneratedPersistenceStateException $actual) {
            self::assertStringNotContainsString($private, (string) $actual);
        }
    }

    public function testPublicationFailureRetainsPrimaryAndCleanupSeparately(): void
    {
        $primary = new RuntimeException('private-primary');
        $cleanup = new RuntimeException('private-cleanup');
        $failure = GeneratedPersistenceStatePublicationException::failed($primary, $cleanup);

        self::assertSame($primary, $failure->primaryFailure());
        self::assertSame($cleanup, $failure->cleanupFailure());
        self::assertSame($primary, $failure->getPrevious());
        self::assertStringNotContainsString('private-primary', (string) $failure);
        self::assertStringNotContainsString('private-cleanup', (string) $failure);
    }
}
