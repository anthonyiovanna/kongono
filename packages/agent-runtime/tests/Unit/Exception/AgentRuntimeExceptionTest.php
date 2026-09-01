<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Unit\Exception;

use Kongono\AgentRuntime\Exception\AgentRuntimeCleanupException;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationAndCleanupException;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationException;
use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentRuntimeExceptionTest extends TestCase
{
    public function testFamiliesPreserveExactThrowablesAndFixedDiagnostics(): void
    {
        $primary = new RuntimeException('SECRET-primary');
        $cleanup = new RuntimeException('SECRET-cleanup');
        $operation = AgentRuntimeOperationException::failed('proposal', $primary);
        self::assertSame($primary, $operation->getPrevious());
        self::assertSame('KGN-AGENT-RUNTIME-1331', $operation->diagnosticId());

        $cleanupFailure = new AgentRuntimeCleanupException([$cleanup]);
        self::assertSame([$cleanup], $cleanupFailure->cleanupFailures());
        $combined = new AgentRuntimeOperationAndCleanupException($operation, [$cleanup]);
        self::assertSame($operation, $combined->primaryFailure());
        self::assertSame([$cleanup], $combined->cleanupFailures());

        foreach ([InvalidAgentRuntimeException::invalid('proposal_mismatch'), $operation, $cleanupFailure, $combined] as $failure) {
            self::assertStringNotContainsString('SECRET', (string) $failure);
        }
    }
}
