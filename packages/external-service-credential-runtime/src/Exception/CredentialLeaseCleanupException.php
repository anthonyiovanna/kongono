<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Diagnostic\CredentialLeaseRuntimeDiagnostic;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Throwable;

/** Preserves the exact one-item retirement failure list after an otherwise completed operation. */
#[Api(ApiStatus::Experimental)]
final class CredentialLeaseCleanupException extends ExternalServiceCredentialRuntimeException
{
    /** @var array{Throwable} */
    private readonly array $cleanupFailures;

    /** @param array<array-key, mixed> $cleanupFailures */
    public function __construct(private readonly CredentialLeaseRuntimeResult $terminalResult, array $cleanupFailures)
    {
        if (!array_is_list($cleanupFailures)
            || 1 !== count($cleanupFailures)
            || !$cleanupFailures[0] instanceof Throwable
            || CredentialLeaseRuntimeOutcome::RetirementUncertain !== $terminalResult->outcome()
            || $terminalResult->cleanupComplete()) {
            throw InvalidCredentialLeaseRuntimeException::invalid('cleanup_failures_relation');
        }
        $this->cleanupFailures = [$cleanupFailures[0]];
        parent::__construct(CredentialLeaseRuntimeDiagnostic::RetirementFailed, self::context($terminalResult), $cleanupFailures[0]);
    }

    public function terminalResult(): CredentialLeaseRuntimeResult
    {
        return $this->terminalResult;
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
