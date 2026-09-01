<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports a bounded profile invariant without exposing rejected evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentApplicationProfileException extends AgentApplicationException
{
    public static function invalid(string $violation, ?int $position = null, ?Throwable $previous = null): self
    {
        $allowed = [
            'profile_identity_grammar', 'declaration_artifact_mismatch', 'admission_denied', 'declaration_plan_mismatch',
            'principal_mismatch', 'capability_count_mismatch', 'capability_order_or_identity_mismatch',
            'required_capability_not_permitted', 'constraint_count_mismatch', 'constraint_mismatch',
            'evidence_count_mismatch', 'evidence_mismatch', 'generated_profile_mismatch',
        ];
        $safe = in_array($violation, $allowed, true) ? $violation : 'unsupported_violation';
        if (null !== $position && $position < 0) {
            $safe = 'unsupported_violation';
        }

        return new self(AgentApplicationDiagnostic::InvalidProfile, ['violation' => $safe], $previous);
    }
}
