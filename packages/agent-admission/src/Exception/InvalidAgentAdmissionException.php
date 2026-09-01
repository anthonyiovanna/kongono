<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Exception;

use Kongono\AgentAdmission\Diagnostic\AgentAdmissionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports one bounded invalid admission value or structural input. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentAdmissionException extends AgentAdmissionException
{
    private const array REQUEST = [
        'requested_capabilities_not_list', 'requested_capabilities_count', 'invalid_requested_capability_entry',
        'duplicate_requested_capability', 'noncanonical_requested_capability_order', 'authorization_evidence_not_list',
        'authorization_evidence_count', 'invalid_authorization_evidence_entry', 'duplicate_authorization_evidence',
        'noncanonical_authorization_evidence_order', 'evidence_without_request', 'artifact_bytes_limit',
    ];
    private const array AUTHORITY = [
        'admission_identity_grammar', 'declaration_digest_grammar', 'capability_decisions_not_list',
        'capability_decisions_count', 'invalid_capability_decision_entry', 'duplicate_capability_decision',
        'constraints_not_list', 'constraints_count', 'invalid_constraint_entry', 'duplicate_constraint',
        'noncanonical_constraint_order', 'evidence_references_not_list', 'evidence_references_count',
        'invalid_evidence_reference_entry', 'duplicate_evidence_reference', 'noncanonical_evidence_reference_order',
        'capability_decision_shape',
    ];

    public static function invalid(
        string $violation,
        ?int $count = null,
        ?int $position = null,
        ?Throwable $previous = null,
    ): self {
        $diagnostic = in_array($violation, self::REQUEST, true)
            ? AgentAdmissionDiagnostic::InvalidRequest
            : AgentAdmissionDiagnostic::InvalidAuthorityEvidence;

        return new self($diagnostic, self::context($violation, $count, $position), $previous);
    }

    public static function artifact(int $bytes, ?Throwable $previous = null): self
    {
        return new self(
            AgentAdmissionDiagnostic::ArtifactValidationFailed,
            ['violation' => 'artifact_invalid_or_stale', 'artifact_bytes' => max(0, $bytes)],
            $previous,
        );
    }

    public static function canonical(?Throwable $previous = null): self
    {
        return new self(AgentAdmissionDiagnostic::CanonicalizationFailed, ['violation' => 'canonical_json_failed'], $previous);
    }

    /** @return array<string, string|int|bool> */
    private static function context(string $violation, ?int $count, ?int $position): array
    {
        $allowed = [...self::REQUEST, ...self::AUTHORITY];
        $context = ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'];
        if (null !== $count) {
            $context['entry_count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['entry_position'] = max(0, $position);
        }

        return $context;
    }
}
