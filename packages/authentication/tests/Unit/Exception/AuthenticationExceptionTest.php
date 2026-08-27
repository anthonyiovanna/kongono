<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit\Exception;

use Kongono\Authentication\Exception\AuthenticationCleanupException;
use Kongono\Authentication\Exception\AuthenticationOperationAndCleanupException;
use Kongono\Authentication\Exception\ExceptionInterface;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthenticationExceptionTest extends TestCase
{
    public function testEveryValidationFactoryUsesItsExactDiagnosticWithoutRejectedState(): void
    {
        $failures = [
            [InvalidAuthenticationValueException::validation(), 'KGN-AUTHENTICATION-1001'],
            [InvalidAuthenticationValueException::registration(), 'KGN-AUTHENTICATION-1002'],
            [InvalidAuthenticationValueException::evidence(), 'KGN-AUTHENTICATION-1003'],
            [InvalidAuthenticationValueException::verification(), 'KGN-AUTHENTICATION-1004'],
            [InvalidAuthenticationValueException::freshness(), 'KGN-AUTHENTICATION-1005'],
            [InvalidAuthenticationValueException::strength(), 'KGN-AUTHENTICATION-1006'],
        ];

        foreach ($failures as [$failure, $identifier]) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertSame($identifier, $failure->diagnosticId());
            self::assertSame($identifier . ': ' . $failure->diagnosticTitle(), (string) $failure);
            self::assertNotSame('', $failure->diagnosticRemediation());
            self::assertSame(
                ['package', 'failure_category', 'remediation'],
                array_map(static fn($field): string => $field->key(), $failure->diagnosticContext()->fields()),
            );
        }
    }

    public function testCleanupExceptionPreservesExactCauseWithoutDisclosingIt(): void
    {
        $cause = new RuntimeException('synthetic private cleanup detail');
        $failure = AuthenticationCleanupException::from($cause);

        self::assertSame($cause, $failure->cleanupFailure());
        self::assertSame($cause, $failure->getPrevious());
        self::assertStringNotContainsString($cause->getMessage(), (string) $failure);
        self::assertStringNotContainsString($cause->getMessage(), $failure->getMessage());
    }

    public function testCombinedExceptionPreservesExactCausesAndPrimaryPrevious(): void
    {
        $operation = new RuntimeException('synthetic private operation detail');
        $cleanup = new RuntimeException('synthetic private cleanup detail');
        $failure = AuthenticationOperationAndCleanupException::from($operation, $cleanup);

        self::assertSame($operation, $failure->operationFailure());
        self::assertSame($cleanup, $failure->cleanupFailure());
        self::assertSame($operation, $failure->getPrevious());
        self::assertStringNotContainsString($operation->getMessage(), (string) $failure);
        self::assertStringNotContainsString($cleanup->getMessage(), (string) $failure);
    }
}
