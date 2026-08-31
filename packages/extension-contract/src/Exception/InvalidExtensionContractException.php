<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Diagnostic\ExtensionContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct an exact extension contract value. */
#[Api(ApiStatus::Experimental)]
final class InvalidExtensionContractException extends ExtensionContractException
{
    private const array IDENTITY_VIOLATIONS = ['extension_identity_grammar', 'capability_identity_grammar', 'contribution_identity_grammar', 'contribution_schema_name_grammar'];
    private const array VERSION_VIOLATIONS = ['extension_version_grammar', 'capability_version_range', 'contribution_schema_version_range'];
    private const array PROVENANCE_VIOLATIONS = ['source_identity_grammar', 'source_digest_grammar'];
    private const array CAPABILITY_VIOLATIONS = ['capability_purpose_grammar'];
    private const array CONTRIBUTION_VIOLATIONS = ['contribution_source_digest_grammar'];
    private const array DECLARATION_VIOLATIONS = ['capability_requests_not_list', 'invalid_capability_request_entry', 'duplicate_capability_request', 'non_canonical_capability_request_order', 'contributions_not_list', 'invalid_contribution_entry', 'duplicate_contribution_identity', 'non_canonical_contribution_order'];
    private const array LIMIT_VIOLATIONS = ['capability_request_count', 'contribution_count'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExtensionContractDiagnostic::InvalidIdentity, ['violation' => self::safeViolation($violation, self::IDENTITY_VIOLATIONS)], $previous);
    }

    public static function invalidVersion(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExtensionContractDiagnostic::InvalidVersion, ['violation' => self::safeViolation($violation, self::VERSION_VIOLATIONS)], $previous);
    }

    public static function invalidProvenance(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExtensionContractDiagnostic::InvalidProvenance, ['violation' => self::safeViolation($violation, self::PROVENANCE_VIOLATIONS)], $previous);
    }

    public static function invalidCapabilityRequest(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExtensionContractDiagnostic::InvalidCapabilityRequest, ['violation' => self::safeViolation($violation, self::CAPABILITY_VIOLATIONS)], $previous);
    }

    public static function invalidContribution(string $violation, ?Throwable $previous = null): self
    {
        return new self(ExtensionContractDiagnostic::InvalidContribution, ['violation' => self::safeViolation($violation, self::CONTRIBUTION_VIOLATIONS)], $previous);
    }

    public static function invalidDeclaration(string $violation, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => self::safeViolation($violation, self::DECLARATION_VIOLATIONS)];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return new self(ExtensionContractDiagnostic::InvalidDeclaration, $context, $previous);
    }

    public static function contractLimitExceeded(string $violation, int $entryCount, ?Throwable $previous = null): self
    {
        return new self(
            ExtensionContractDiagnostic::ContractLimitExceeded,
            ['violation' => self::safeViolation($violation, self::LIMIT_VIOLATIONS), 'entry_count' => max(0, $entryCount)],
            $previous,
        );
    }

    /** @param list<string> $allowed */
    private static function safeViolation(string $violation, array $allowed): string
    {
        return in_array($violation, $allowed, true) ? $violation : 'unsupported_violation';
    }
}
