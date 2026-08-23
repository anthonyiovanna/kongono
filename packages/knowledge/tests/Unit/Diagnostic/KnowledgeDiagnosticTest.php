<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\ExceptionInterface;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Exception\KnowledgeException;
use Kongono\Knowledge\Exception\KnowledgePublicationException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class KnowledgeDiagnosticTest extends TestCase
{
    public function testCatalogHasExactCasesIdentifiersTitlesRemediationAndSchemas(): void
    {
        self::assertSame([
            'KGN-KNOWLEDGE-1001', 'KGN-KNOWLEDGE-1002', 'KGN-KNOWLEDGE-1003',
            'KGN-KNOWLEDGE-1004', 'KGN-KNOWLEDGE-1005', 'KGN-KNOWLEDGE-1006',
            'KGN-KNOWLEDGE-1007', 'KGN-KNOWLEDGE-1008', 'KGN-KNOWLEDGE-1009',
            'KGN-KNOWLEDGE-1010', 'KGN-KNOWLEDGE-1011',
        ], array_column(KnowledgeDiagnostic::cases(), 'value'));
        foreach (KnowledgeDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $context = $diagnostic->context($this->tokenFor($diagnostic));
            self::assertCount(1, $context->fields());
            self::assertSame('violation', $context->fields()[0]->key());
            self::assertSame(DiagnosticDisclosure::Safe, $context->fields()[0]->disclosure());
        }
    }

    public function testDiagnosticIdentifiersAreUniqueAndMatchKnowledgeGrammar(): void
    {
        $ids = array_column(KnowledgeDiagnostic::cases(), 'value');
        self::assertCount(11, array_unique($ids));
        foreach ($ids as $id) {
            self::assertMatchesRegularExpression('/^KGN-KNOWLEDGE-1\d{3}$/D', $id);
        }
    }

    public function testMarkerBaseAndConcreteReflectionShapesAreSeparated(): void
    {
        $marker = new \ReflectionClass(ExceptionInterface::class);
        $base = new \ReflectionClass(KnowledgeException::class);
        $baseParent = $base->getParentClass();
        self::assertInstanceOf(\ReflectionClass::class, $baseParent);
        self::assertTrue($marker->isInterface());
        self::assertTrue($base->isAbstract());
        self::assertSame(RuntimeException::class, $baseParent->getName());
        self::assertTrue($base->getConstructor()?->isFinal());
        foreach ([InvalidKnowledgeArtifactException::class, InvalidKnowledgeSourceException::class, KnowledgePublicationException::class, StaleKnowledgeArtifactException::class] as $class) {
            $reflection = new \ReflectionClass($class);
            $parent = $reflection->getParentClass();
            self::assertInstanceOf(\ReflectionClass::class, $parent);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(ExceptionInterface::class));
            self::assertSame(KnowledgeException::class, $parent->getName());
        }
    }

    public function testExceptionReturnsTheSameDetachedContextObject(): void
    {
        $exception = new InvalidKnowledgeSourceException(KnowledgeDiagnostic::InvalidSourcePath, 'path_escape');
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
        self::assertSame('path_escape', $exception->diagnosticContext()->fields()[0]->value());
        self::assertSame(KnowledgeDiagnostic::InvalidSourcePath->title(), $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    public function testMessagesContextsAndStringificationExcludeRejectedRawValues(): void
    {
        $secret = 'raw-secret-value';
        $exception = new InvalidKnowledgeArtifactException(KnowledgeDiagnostic::InvalidArtifact, 'invalid_json', new RuntimeException($secret));
        self::assertStringNotContainsString($secret, $exception->getMessage());
        self::assertStringNotContainsString($secret, (string) $exception->diagnosticContext()->fields()[0]->value());
        self::assertStringNotContainsString($secret, (string) $exception);
        self::assertSame($secret, $exception->getPrevious()?->getMessage());
    }

    public function testNativeCauseIdentityIsPreservedWithoutChangingDiagnosticPriority(): void
    {
        $cause = new RuntimeException('private native failure');
        $exception = new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_write', $cause);
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame('KGN-KNOWLEDGE-1009', $exception->diagnosticId());
        self::assertSame('temporary_write', $exception->diagnosticContext()->fields()[0]->value());
        self::assertSame(KnowledgeDiagnostic::PublicationFailed->title(), $exception->getMessage());
    }

    private function tokenFor(KnowledgeDiagnostic $diagnostic): string
    {
        return match ($diagnostic) {
            KnowledgeDiagnostic::InvalidInventory => 'invalid_json',
            KnowledgeDiagnostic::InvalidSourcePath => 'path_escape',
            KnowledgeDiagnostic::SourceUnavailable => 'source_missing',
            KnowledgeDiagnostic::UnsafeSource => 'non_regular_source',
            KnowledgeDiagnostic::SourceLimitExceeded => 'source_bytes',
            KnowledgeDiagnostic::InvalidSourceEncoding => 'invalid_utf8',
            KnowledgeDiagnostic::InvalidArtifact => 'invalid_json',
            KnowledgeDiagnostic::StaleArtifact => 'source_state_mismatch',
            KnowledgeDiagnostic::PublicationFailed => 'atomic_link',
            KnowledgeDiagnostic::InvalidCommand => 'argument_count',
            KnowledgeDiagnostic::UnexpectedCommandFailure => 'unexpected_failure',
        };
    }
}
