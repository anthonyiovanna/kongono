<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Unit\Exception;

use Kongono\ModelInvocationAgentBridge\Exception\ExceptionInterface;
use Kongono\ModelInvocationAgentBridge\Exception\InvalidModelInvocationAgentBridgeException;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(InvalidModelInvocationAgentBridgeException::class)]
#[CoversClass(ProposalBridgeRejectedException::class)]
final class ModelInvocationAgentBridgeExceptionTest extends TestCase
{
    public function testUnknownLocalValuesNormalizeWithoutDisclosingPreviousMessages(): void
    {
        $previous = new RuntimeException('secret previous payload');
        $exception = InvalidModelInvocationAgentBridgeException::invalid('secret violation', 'secret stage', $previous);

        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('KGN-MODEL-BRIDGE-1425', $exception->diagnosticId());
        self::assertStringContainsString('unsupported_violation', (string) $exception);
        self::assertStringNotContainsString('secret', $exception->getMessage());
        self::assertStringNotContainsString('secret', (string) $exception);
    }

    public function testRejectionsCarryOnlyClosedSafeCountsAndVocabulary(): void
    {
        $exception = ProposalBridgeRejectedException::ineligible('result_mode_ineligible', 'result', 'text', 'failed', 2);

        self::assertSame('KGN-MODEL-BRIDGE-1426', $exception->diagnosticId());
        self::assertStringContainsString('mode=text', (string) $exception);
        self::assertStringContainsString('outcome=failed', (string) $exception);
        self::assertStringContainsString('attempt_count=2', (string) $exception);
    }
}
