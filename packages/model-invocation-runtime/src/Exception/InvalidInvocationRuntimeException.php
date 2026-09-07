<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationRuntime\Diagnostic\ModelInvocationRuntimeDiagnostic;

/** Reports a direct package invariant failure using one closed safe label. */
#[Api(ApiStatus::Experimental)]
final class InvalidInvocationRuntimeException extends ModelInvocationRuntimeException
{
    private string $violation;

    private const array VIOLATIONS = [
        'events_not_list', 'invalid_event_entry', 'terminal_result_invariant', 'result_outcome_relation',
        'result_identity_relation', 'attempt_outcome_relation', 'reentrancy', 'clock_negative',
        'clock_regression', 'clock_overflow', 'adapter_identity', 'emission_shape', 'stream_protocol',
        'result_contract', 'usage_protocol', 'cost_protocol', 'budget_exceeded', 'operation_incomplete',
        'cleanup_failures_empty', 'rejected_receipt_relation', 'operation_receipt_relation',
        'cleanup_receipt_relation', 'diagnostic_count_range',
    ];

    public static function invalid(string $violation): self
    {
        $safe = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $exception = new self(ModelInvocationRuntimeDiagnostic::InvalidInput, ['violation' => $safe]);
        $exception->violation = $safe;

        return $exception;
    }

    public function violation(): string
    {
        return $this->violation;
    }
}
