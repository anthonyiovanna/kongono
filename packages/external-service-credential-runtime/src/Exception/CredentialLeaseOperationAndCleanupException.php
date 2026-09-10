<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Diagnostic\CredentialLeaseRuntimeDiagnostic;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Throwable;

/** Preserves exact primary and retirement failures as distinct uncertain evidence. */
#[Api(ApiStatus::Experimental)]
final class CredentialLeaseOperationAndCleanupException extends ExternalServiceCredentialRuntimeException
{
    /** @var array{Throwable} */
    private readonly array $cleanupFailures;

    /** @param array<array-key, mixed> $cleanupFailures */
    public function __construct(
        private readonly CredentialLeaseRuntimeResult $terminalResult,
        private readonly Throwable $primaryFailure,
        array $cleanupFailures,
    ) {
        if (!array_is_list($cleanupFailures)
            || 1 !== count($cleanupFailures)
            || !$cleanupFailures[0] instanceof Throwable
            || CredentialLeaseRuntimeOutcome::RetirementUncertain !== $terminalResult->outcome()
            || $terminalResult->cleanupComplete()) {
            throw InvalidCredentialLeaseRuntimeException::invalid('cleanup_failures_relation');
        }
        $this->cleanupFailures = [$cleanupFailures[0]];
        parent::__construct(CredentialLeaseRuntimeDiagnostic::RetirementFailed, self::context($terminalResult), $primaryFailure);
    }

    public function terminalResult(): CredentialLeaseRuntimeResult
    {
        return $this->terminalResult;
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    /** @return array{Throwable} */
    public function cleanupFailures(): array
    {
        return [...$this->cleanupFailures];
    }

    /** @return array<string, string|int|bool> */
    private static function context(CredentialLeaseRuntimeResult $result): array
    {
        return [
            'stage' => 'retiring',
            'violation' => $result->failure()?->reason() ?? 'retirement_failed',
            'outcome' => $result->outcome()->value,
            'state' => $result->state()->value,
            'acquisition_attempt_count' => $result->acquisitionAttemptCount(),
            'material_access_attempt_count' => $result->materialAccessAttemptCount(),
            'retirement_attempt_count' => $result->retirementAttemptCount(),
            'cleanup_complete' => $result->cleanupComplete(),
        ];
    }
}
