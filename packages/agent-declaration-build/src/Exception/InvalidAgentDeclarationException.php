<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Exception;

use Kongono\AgentDeclarationBuild\Diagnostic\AgentDeclarationBuildDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports one bounded failure to construct a canonical declaration value. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentDeclarationException extends AgentDeclarationBuildException
{
    public static function invalid(string $violation, ?int $count = null, ?int $position = null, ?Throwable $previous = null): self
    {
        return new self(AgentDeclarationBuildDiagnostic::InvalidDeclaration, self::context($violation, $count, $position), $previous);
    }

    public static function limit(string $violation, int $count): self
    {
        return new self(AgentDeclarationBuildDiagnostic::BuildLimitExceeded, self::context($violation, $count));
    }

    /** @return array<string, string|int|bool> */
    private static function context(string $violation, ?int $count = null, ?int $position = null): array
    {
        $allowed = [
            'declaration_identity_grammar',
            'capability_requests_not_list', 'invalid_capability_requests_entry', 'duplicate_capability_requests_entry', 'noncanonical_capability_requests_order', 'capability_requests_count',
            'constraints_not_list', 'invalid_constraints_entry', 'duplicate_constraints_entry', 'noncanonical_constraints_order', 'constraints_count',
            'evidence_references_not_list', 'invalid_evidence_references_entry', 'duplicate_evidence_references_entry', 'noncanonical_evidence_references_order', 'evidence_references_count',
        ];
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
