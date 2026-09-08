<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Diagnostic\NetworkEgressRuntimeDiagnostic;

/** Reports one closed local invariant failure without retaining caller content. */
#[Api(ApiStatus::Experimental)]
final class InvalidEgressRuntimeException extends NetworkEgressRuntimeException
{
    private const array VIOLATIONS = [
        'address_grammar', 'resolution_list', 'resolution_entry', 'resolution_duplicate', 'selected_membership',
        'resolution_result_shape', 'emission_bounds', 'emission_shape', 'response_shape', 'result_bounds',
        'clock_negative', 'clock_regression', 'header_budget', 'policy_reference', 'budget_reference',
        'origin_denied', 'header_posture', 'proxy_unsupported', 'resolution_candidates', 'address_scope',
        'literal_address', 'capability_instability', 'transport_capability', 'protocol_capability',
        'emission_limit', 'transfer_regression', 'protocol_change', 'request_budget', 'response_budget',
        'buffer_budget', 'evidence_budget', 'reentrancy', 'operation_incomplete', 'terminal_exhaustion',
        'total_timeout', 'connect_timeout', 'first_byte_timeout', 'cleanup_failures_empty', 'result_relation',
        'unsupported_violation',
    ];

    private string $violation;

    public static function invalid(string $violation): self
    {
        $safe = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $exception = new self(NetworkEgressRuntimeDiagnostic::InvalidInput, ['violation' => $safe]);
        $exception->violation = $safe;

        return $exception;
    }

    public function violation(): string
    {
        return $this->violation;
    }
}
