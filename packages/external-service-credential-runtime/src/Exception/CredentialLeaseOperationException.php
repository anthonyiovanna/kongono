<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Diagnostic\CredentialLeaseRuntimeDiagnostic;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Throwable;

/** Preserves one exact primary failure behind bounded cleanup-complete evidence. */
#[Api(ApiStatus::Experimental)]
final class CredentialLeaseOperationException extends ExternalServiceCredentialRuntimeException
{
    public function __construct(
        private readonly CredentialLeaseRuntimeResult $terminalResult,
        private readonly Throwable $primaryFailure,
        string $stage,
        int $limit = 0,
        int $observedCount = 0,
    ) {
        $failure = $terminalResult->failure();
        if (null === $failure
            || in_array($terminalResult->outcome(), [CredentialLeaseRuntimeOutcome::Succeeded, CredentialLeaseRuntimeOutcome::RetirementUncertain], true)
            || !$terminalResult->cleanupComplete()
            || $limit < 0
            || $observedCount < 0) {
            throw InvalidCredentialLeaseRuntimeException::invalid('exception_result_relation');
        }
        $diagnostic = self::diagnostic($failure->kind());
        $context = self::context($terminalResult, self::stage($stage), $failure->reason());
        if (CredentialLeaseRuntimeDiagnostic::LifetimeExceeded === $diagnostic) {
            $context['limit'] = $limit;
            $context['observed_count'] = $observedCount;
        }
        parent::__construct($diagnostic, $context, $primaryFailure);
    }

    public function terminalResult(): CredentialLeaseRuntimeResult
    {
        return $this->terminalResult;
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    private static function diagnostic(CredentialLeaseRuntimeFailureKind $kind): CredentialLeaseRuntimeDiagnostic
    {
        return match ($kind) {
            CredentialLeaseRuntimeFailureKind::Clock => CredentialLeaseRuntimeDiagnostic::ClockFailed,
            CredentialLeaseRuntimeFailureKind::Source => CredentialLeaseRuntimeDiagnostic::SourceFailed,
            CredentialLeaseRuntimeFailureKind::LeaseProtocol => CredentialLeaseRuntimeDiagnostic::LeaseProtocolViolated,
            CredentialLeaseRuntimeFailureKind::Lifetime => CredentialLeaseRuntimeDiagnostic::LifetimeExceeded,
            CredentialLeaseRuntimeFailureKind::Consumer => CredentialLeaseRuntimeDiagnostic::ConsumerFailed,
            CredentialLeaseRuntimeFailureKind::Retirement => throw InvalidCredentialLeaseRuntimeException::invalid('diagnostic_relation'),
        };
    }

    /** @return array<string, string|int|bool> */
    private static function context(CredentialLeaseRuntimeResult $result, string $stage, string $violation): array
    {
        return [
            'stage' => $stage,
            'violation' => $violation,
            'outcome' => $result->outcome()->value,
            'state' => $result->state()->value,
            'acquisition_attempt_count' => $result->acquisitionAttemptCount(),
            'material_access_attempt_count' => $result->materialAccessAttemptCount(),
            'retirement_attempt_count' => $result->retirementAttemptCount(),
            'cleanup_complete' => $result->cleanupComplete(),
        ];
    }

    private static function stage(string $stage): string
    {
        return in_array($stage, ['prepared', 'acquiring', 'acquired', 'using', 'retired'], true) ? $stage : 'unsupported_stage';
    }
}
