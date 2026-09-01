<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing admission failure metadata. */
#[Api(ApiStatus::Internal)]
enum AgentAdmissionDiagnostic: string
{
    case InvalidRequest = 'KGN-AGENT-ADMISSION-1320';
    case InvalidAuthorityEvidence = 'KGN-AGENT-ADMISSION-1321';
    case ArtifactValidationFailed = 'KGN-AGENT-ADMISSION-1322';
    case CanonicalizationFailed = 'KGN-AGENT-ADMISSION-1323';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema(
            $this->operation(),
            $this->invariant(),
            [
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('artifact_bytes', 'Artifact bytes', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            ],
        );
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Agent admission request is invalid',
            self::InvalidAuthorityEvidence => 'Agent authority evidence is invalid',
            self::ArtifactValidationFailed => 'Agent declaration artifact validation failed',
            self::CanonicalizationFailed => 'Agent authority plan canonicalization failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Supply bounded canonical requested capabilities and matching evidence',
            self::InvalidAuthorityEvidence => 'Supply one internally consistent immutable capability decision set',
            self::ArtifactValidationFailed => 'Supply exact current canonical Phase 13C declaration artifact bytes',
            self::CanonicalizationFailed => 'Supply supported canonical typed admission values within fixed bounds',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Create Agent admission request',
            self::InvalidAuthorityEvidence => 'Construct Agent authority evidence',
            self::ArtifactValidationFailed => 'Validate current Agent declaration artifact',
            self::CanonicalizationFailed => 'Derive deterministic Agent admission identity',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Request and evidence lists must be typed, bounded, unique, and in strict ordinal order',
            self::InvalidAuthorityEvidence => 'Capability evidence shapes must remain non-contradictory and inert',
            self::ArtifactValidationFailed => 'Admission must bind exact structurally valid current declaration evidence',
            self::CanonicalizationFailed => 'Authority-plan identity bytes must be deterministic and canonical',
        };
    }
}
