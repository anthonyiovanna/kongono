<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Diagnostic\ModelInvocationAgentBridgeDiagnostic;
use Throwable;

/** Carries one bounded typed bridge rejection without disclosing model content. */
#[Api(ApiStatus::Experimental)]
final class ProposalBridgeRejectedException extends ModelInvocationAgentBridgeException
{
    private const array INELIGIBLE_VIOLATIONS = [
        'runtime_not_retired', 'cleanup_incomplete', 'outcome_not_succeeded', 'attempt_count_not_one',
        'result_missing', 'receipt_result_mismatch', 'invocation_mismatch', 'profile_mismatch',
        'usage_relation_mismatch', 'cost_relation_mismatch', 'result_mode_ineligible', 'finish_reason_ineligible',
    ];
    private const array MAPPING_VIOLATIONS = ['action_name_mismatch', 'capability_not_permitted'];
    private const array PROJECTION_VIOLATIONS = ['usage_missing', 'usage_selector_missing', 'canonical_json_encoding'];
    private const array STAGES = ['runtime', 'receipt', 'result', 'mapping', 'capability', 'usage', 'evidence'];

    public static function ineligible(
        string $violation,
        string $stage,
        ?string $mode = null,
        ?string $outcome = null,
        ?int $attemptCount = null,
        ?Throwable $previous = null,
    ): self {
        $context = ['violation' => self::safe($violation, self::INELIGIBLE_VIOLATIONS), 'stage' => self::safe($stage, self::STAGES)];
        if (null !== $mode) {
            $context['mode'] = self::safe($mode, ['text', 'structured', 'action_suggestion']);
        }
        if (null !== $outcome) {
            $context['outcome'] = self::safe($outcome, ['succeeded', 'failed', 'cancelled', 'rejected', 'uncertain']);
        }
        if (null !== $attemptCount) {
            $context['attempt_count'] = max(0, $attemptCount);
        }

        return new self(ModelInvocationAgentBridgeDiagnostic::IneligibleResult, $context, $previous);
    }

    public static function mapping(string $violation, string $stage, int $permittedCount): self
    {
        return new self(
            ModelInvocationAgentBridgeDiagnostic::MappingRejected,
            [
                'violation' => self::safe($violation, self::MAPPING_VIOLATIONS),
                'stage' => self::safe($stage, self::STAGES),
                'permitted_count' => max(0, $permittedCount),
            ],
        );
    }

    public static function projection(string $violation, string $stage, int $usageCount, ?Throwable $previous = null): self
    {
        return new self(
            ModelInvocationAgentBridgeDiagnostic::ProjectionFailed,
            [
                'violation' => self::safe($violation, self::PROJECTION_VIOLATIONS),
                'stage' => self::safe($stage, self::STAGES),
                'usage_count' => max(0, $usageCount),
            ],
            $previous,
        );
    }

    /** @param list<string> $allowed */
    private static function safe(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : 'unsupported_violation';
    }
}
