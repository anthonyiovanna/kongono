<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;
use Throwable;

/** Preserves one exact primary operation failure behind bounded lifecycle evidence. */
#[Api(ApiStatus::Experimental)]
final class InvocationOperationException extends ModelInvocationRuntimeException
{
    private const array STAGES = ['adapter_identity', 'clock', 'cancellation', 'open', 'pull', 'protocol', 'budget', 'result', 'lifecycle'];

    private const array VIOLATIONS = [
        'initial_probe', 'adapter_open', 'adapter_pull', 'probe_failed', 'clock_negative', 'clock_regression',
        'operation_incomplete', 'trailing_emission', 'result_required', 'event_required', 'stream_events',
        'retained_evidence', 'output_bytes', 'stream_protocol', 'usage_protocol', 'cost_protocol',
        'budget_exceeded', 'result_contract',
    ];

    public function __construct(
        private readonly InvocationReceipt $receipt,
        ModelInvocationRuntimeDiagnostic $diagnostic,
        string $stage,
        string $violation,
        Throwable $previous,
        int $limit = 0,
        int $observedCount = 0,
    ) {
        if (InvocationOutcome::Failed !== $receipt->outcome() || 1 !== $receipt->attemptCount() || !$receipt->cleanupComplete()) {
            throw InvalidInvocationRuntimeException::invalid('operation_receipt_relation');
        }
        if ($limit < 0 || $observedCount < 0) {
            throw InvalidInvocationRuntimeException::invalid('diagnostic_count_range');
        }
        $safeStage = in_array($stage, self::STAGES, true) ? $stage : 'unsupported_stage';
        $safeViolation = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $context = ModelInvocationRuntimeDiagnostic::BudgetExceeded === $diagnostic
            ? ['stage' => $safeStage, 'violation' => $safeViolation, 'limit' => $limit, 'observed_count' => $observedCount]
            : ['stage' => $safeStage, 'violation' => $safeViolation];
        parent::__construct($diagnostic, $context, $previous);
    }

    public function receipt(): InvocationReceipt
    {
        return $this->receipt;
    }
}
