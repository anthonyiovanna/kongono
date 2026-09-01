<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Unit\Exception;

use Kongono\AgentDeclarationBuild\Exception\AgentDeclarationPublicationException;
use Kongono\AgentDeclarationBuild\Exception\ExceptionInterface;
use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationArtifactException;
use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentDeclarationBuildExceptionTest extends TestCase
{
    public function testMarkerAndDeterministicStringification(): void
    {
        $failure = InvalidAgentDeclarationException::invalid('unknown-secret-value', -3, -8);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-AGENT-DECLARATION-1310', $failure->diagnosticId());
        self::assertStringContainsString('unsupported_violation', (string) $failure);
        self::assertStringNotContainsString('unknown-secret-value', (string) $failure);
        self::assertStringContainsString('entry_count=0', (string) $failure);
        self::assertStringContainsString('entry_position=0', (string) $failure);
    }

    public function testPreviousFailureIsPreservedWithoutMessageComposition(): void
    {
        $sentinel = 'PreviousSecretSentinel';
        $previous = new RuntimeException($sentinel);
        $failure = AgentDeclarationPublicationException::failed('invalid_artifact', $previous);
        self::assertSame($previous, $failure->getPrevious());
        self::assertStringNotContainsString($sentinel, $failure->getMessage());
        self::assertStringNotContainsString($sentinel, (string) $failure);

        $artifact = InvalidAgentDeclarationArtifactException::invalid('unknown-' . $sentinel, artifactBytes: -1, previous: $previous);
        self::assertSame(0, $artifact->diagnosticContext()->fields()[1]->value());
        self::assertStringNotContainsString($sentinel, (string) $artifact);
    }
}
