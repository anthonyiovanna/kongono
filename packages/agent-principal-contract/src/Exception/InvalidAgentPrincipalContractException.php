<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Exception;

use Kongono\AgentPrincipalContract\Diagnostic\AgentPrincipalContractDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports one bounded failure to construct an exact Agent Principal contract value. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentPrincipalContractException extends AgentPrincipalContractException
{
    private const array PRINCIPAL_VIOLATIONS = ['principal_kind'];
    private const array IDENTITY_VIOLATIONS = ['operation_reference_grammar', 'intent_identity_grammar', 'proposal_identity_grammar', 'capability_identity_grammar', 'evidence_identity_grammar', 'receipt_identity_grammar'];
    private const array PURPOSE_VIOLATIONS = ['intent_purpose_grammar', 'capability_purpose_grammar'];
    private const array EVIDENCE_VIOLATIONS = ['evidence_digest_grammar', 'provenance_source_identity_grammar', 'provenance_digest_grammar'];
    private const array PROPOSAL_VIOLATIONS = ['constraint_value_range', 'constraints_not_list', 'invalid_constraint_entry', 'duplicate_constraint_kind', 'non_canonical_constraint_order', 'evidence_not_list', 'invalid_evidence_entry', 'duplicate_evidence_reference', 'non_canonical_evidence_order'];
    private const array LIMIT_VIOLATIONS = ['constraint_count', 'evidence_count'];
    private const array RECEIPT_VIOLATIONS = ['receipt_evidence_digest_grammar', 'receipt_relation'];

    public static function invalidPrincipal(string $violation, ?Throwable $previous = null): self
    {
        return new self(AgentPrincipalContractDiagnostic::InvalidPrincipal, ['violation' => self::safeViolation($violation, self::PRINCIPAL_VIOLATIONS)], $previous);
    }

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(AgentPrincipalContractDiagnostic::InvalidIdentity, ['violation' => self::safeViolation($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidPurpose(string $violation, ?Throwable $previous = null): self
    {
        return new self(AgentPrincipalContractDiagnostic::InvalidPurpose, ['violation' => self::safeViolation($violation, self::PURPOSE_VIOLATIONS)], $previous);
    }

    public static function invalidEvidence(string $violation, ?Throwable $previous = null): self
    {
        return new self(AgentPrincipalContractDiagnostic::InvalidEvidence, ['violation' => self::safeViolation($violation, self::EVIDENCE_VIOLATIONS)], $previous);
    }

    public static function invalidProposal(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::PROPOSAL_VIOLATIONS)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return new self(AgentPrincipalContractDiagnostic::InvalidProposal, $context, $previous);
    }

    public static function contractLimitExceeded(string $violation, int $entryCount, ?Throwable $previous = null): self
    {
        return new self(
            AgentPrincipalContractDiagnostic::ContractLimitExceeded,
            ['violation' => self::safeViolation($violation, self::LIMIT_VIOLATIONS), 'entry_count' => max(0, $entryCount)],
            $previous,
        );
    }

    public static function invalidReceipt(string $violation, ?Throwable $previous = null): self
    {
        return new self(AgentPrincipalContractDiagnostic::InvalidReceipt, ['violation' => self::safeViolation($violation, self::RECEIPT_VIOLATIONS)], $previous);
    }

    /** @param list<string> $allowed */
    private static function safeViolation(string $violation, array $allowed): string
    {
        return in_array($violation, $allowed, true) ? $violation : 'unsupported_violation';
    }
}
