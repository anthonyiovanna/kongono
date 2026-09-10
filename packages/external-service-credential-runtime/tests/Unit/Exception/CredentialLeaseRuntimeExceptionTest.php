<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Exception;

use Error;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationAndCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\ExternalServiceCredentialRuntime\Exception\ExceptionInterface;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CredentialLeaseRuntimeExceptionTest extends TestCase
{
    public function testPrimaryCleanupAndCombinedFailuresPreserveExactIdentities(): void
    {
        $primary = new RuntimeException('foreign-primary-sentinel');
        $cleanup = new Error('foreign-cleanup-sentinel');
        $operation = new CredentialLeaseOperationException($this->primaryResult(), $primary, 'using');
        self::assertInstanceOf(ExceptionInterface::class, $operation);
        self::assertSame($primary, $operation->getPrevious());
        self::assertSame($primary, $operation->primaryFailure());
        self::assertSame($this->primaryResult()->outcome(), $operation->terminalResult()->outcome());

        $cleanupOnly = new CredentialLeaseCleanupException($this->cleanupResult(), [$cleanup]);
        self::assertSame($cleanup, $cleanupOnly->getPrevious());
        self::assertSame([$cleanup], $cleanupOnly->cleanupFailures());
        $copy = $cleanupOnly->cleanupFailures();
        $copy[] = $primary;
        self::assertSame([$cleanup], $cleanupOnly->cleanupFailures());

        $combined = new CredentialLeaseOperationAndCleanupException($this->cleanupResult(), $primary, [$cleanup]);
        self::assertSame($primary, $combined->getPrevious());
        self::assertSame($primary, $combined->primaryFailure());
        self::assertSame([$cleanup], $combined->cleanupFailures());

        foreach ([$operation, $cleanupOnly, $combined] as $failure) {
            self::assertStringNotContainsString('foreign-primary-sentinel', $failure->getMessage());
            self::assertStringNotContainsString('foreign-cleanup-sentinel', $failure->getMessage());
            self::assertStringNotContainsString('foreign-primary-sentinel', (string) $failure);
            self::assertStringNotContainsString('foreign-cleanup-sentinel', (string) $failure);
        }
    }

    public function testCleanupFailureListsRejectNonThrowableAndNonListInputs(): void
    {
        foreach ([['not-a-throwable'], ['named' => new Error('cleanup')]] as $cleanupFailures) {
            try {
                new CredentialLeaseCleanupException($this->cleanupResult(), $cleanupFailures);
                self::fail('Invalid cleanup failure evidence must be rejected.');
            } catch (InvalidCredentialLeaseRuntimeException $failure) {
                self::assertSame('KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1601', $failure->diagnosticId());
            }
        }
    }

    private function primaryResult(): CredentialLeaseRuntimeResult
    {
        return new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::MaterialUseUncertain,
            CredentialLeaseRuntimeState::Retired,
            new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Consumer, 'consumer_failed'),
            1,
            1,
            1,
            1,
            true,
        );
    }

    private function cleanupResult(): CredentialLeaseRuntimeResult
    {
        return new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::RetirementUncertain,
            CredentialLeaseRuntimeState::Uncertain,
            new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Retirement, 'retirement_failed'),
            1,
            1,
            1,
            1,
            false,
        );
    }
}
