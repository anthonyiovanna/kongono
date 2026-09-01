<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Exception;

use Kongono\AgentRuntime\Diagnostic\AgentRuntimeDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports one bounded structural or authority-binding invalidity. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentRuntimeException extends AgentRuntimeException
{
    private const array VIOLATIONS = [
        'budget_value_range', 'usage_value_range', 'budget_concurrency', 'budget_exceeds_declaration',
        'reported_usage_range', 'capability_output_limit', 'digest_grammar', 'ports_not_list',
        'port_count', 'invalid_port', 'port_identity_failure', 'duplicate_port', 'noncanonical_port_order',
        'extraneous_port', 'values_not_list', 'value_count', 'invalid_value', 'duplicate_value',
        'noncanonical_value_order', 'receipts_not_list', 'invalid_receipt', 'receipt_order',
        'plan_not_admitted', 'declaration_identity_mismatch', 'declaration_digest_mismatch',
        'principal_mismatch', 'constraints_mismatch', 'evidence_mismatch', 'capability_plan_mismatch',
        'declaration_rebuild_failed', 'proposal_mismatch',
        'proposal_capability_not_permitted', 'proposal_port_missing',
    ];

    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(
            AgentRuntimeDiagnostic::InvalidInput,
            ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation'],
            $previous,
        );
    }
}
