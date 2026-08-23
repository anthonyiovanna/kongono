<?php

declare(strict_types=1);

namespace Kongono\Oshie\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Oshie\Exception\InvalidOshieValueException;

/** Owns the nine closed Safe diagnostic contracts for read-only Oshie. */
#[Api(ApiStatus::Internal)]
enum OshieDiagnostic: string
{
    case InvalidRequest = 'KGN-OSHIE-1001';
    case ArtifactUnavailable = 'KGN-OSHIE-1002';
    case ArtifactStale = 'KGN-OSHIE-1003';
    case InvalidArtifact = 'KGN-OSHIE-1004';
    case CurriculumInvalid = 'KGN-OSHIE-1005';
    case SourceSectionInvalid = 'KGN-OSHIE-1006';
    case CitationResolutionFailed = 'KGN-OSHIE-1007';
    case ResponseLimitExceeded = 'KGN-OSHIE-1008';
    case UnexpectedCommandFailure = 'KGN-OSHIE-1009';

    public function context(string $violation): DiagnosticContext
    {
        if (!in_array($violation, match ($this) {
            self::InvalidRequest => ['operation', 'identifier-grammar', 'answer-grammar', 'answer-option', 'invalid-command-usage'],
            self::ArtifactUnavailable => ['artifact-path', 'artifact-read', 'framework-root', 'source-unavailable'],
            self::ArtifactStale => ['source-state-mismatch'],
            self::InvalidArtifact => ['artifact-shape', 'artifact-name', 'artifact-path', 'artifact-metadata'],
            self::CurriculumInvalid => ['resource-read', 'resource-digest', 'resource-shape', 'resource-canonical', 'resource-order', 'resource-value'],
            self::SourceSectionInvalid => ['heading-missing', 'heading-duplicate', 'section-limit'],
            self::CitationResolutionFailed => ['source-missing', 'authority', 'citation'],
            self::ResponseLimitExceeded => ['artifact-limit', 'response-limit'],
            self::UnexpectedCommandFailure => ['unexpected-failure'],
        }, true)) {
            throw InvalidOshieValueException::diagnosticViolation();
        }

        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
        ), ['violation' => $violation]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Admit one read-only Oshie request',
            self::ArtifactUnavailable => 'Read the explicit framework knowledge artifact',
            self::ArtifactStale => 'Match the artifact to the explicit framework root',
            self::InvalidArtifact => 'Validate the framework knowledge artifact',
            self::CurriculumInvalid => 'Load the fixed package curriculum',
            self::SourceSectionInvalid => 'Extract one exact documented lesson section',
            self::CitationResolutionFailed => 'Resolve one exact artifact citation',
            self::ResponseLimitExceeded => 'Enforce bounded Oshie output',
            self::UnexpectedCommandFailure => 'Run the read-only Oshie command',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidRequest => 'The request must match one exact read-only operation and admitted grammar',
            self::ArtifactUnavailable => 'The explicit local artifact and framework sources must be safely readable',
            self::ArtifactStale => 'Every lesson must use an artifact equal to the exact current framework source state',
            self::InvalidArtifact => 'Artifact bytes and metadata must be canonical, internally valid, and untampered',
            self::CurriculumInvalid => 'The package-owned curriculum must equal the canonical closed six-record profile',
            self::SourceSectionInvalid => 'A configured heading must resolve exactly once to one complete bounded section',
            self::CitationResolutionFailed => 'Every teaching value must bind to one exact admitted source and line range',
            self::ResponseLimitExceeded => 'Oshie must fail closed before any partial or truncated response is returned',
            self::UnexpectedCommandFailure => 'Unexpected failure must remain bounded and not disclose native or rejected data',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Use a valid read-only Oshie request',
            self::ArtifactUnavailable => 'Provide the explicit framework knowledge artifact',
            self::ArtifactStale => 'Regenerate the framework knowledge artifact',
            self::InvalidArtifact => 'Validate the framework knowledge artifact',
            self::CurriculumInvalid => 'Restore the fixed Oshie curriculum',
            self::SourceSectionInvalid => 'Restore the exact documented lesson section',
            self::CitationResolutionFailed => 'Restore the exact cited knowledge source',
            self::ResponseLimitExceeded => 'Reduce the bounded Oshie response',
            self::UnexpectedCommandFailure => 'Inspect the Oshie command boundary',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Use the documented catalog, lesson, exercise, or check grammar',
            self::ArtifactUnavailable => 'Restore the explicit ordinary artifact and exact readable framework root',
            self::ArtifactStale => 'Generate a new artifact from the current exact framework source state',
            self::InvalidArtifact => 'Regenerate and validate the content-addressed canonical artifact',
            self::CurriculumInvalid => 'Restore the canonical package-owned Phase 4 foundation curriculum',
            self::SourceSectionInvalid => 'Restore the unique configured heading and keep its section within bounds',
            self::CitationResolutionFailed => 'Restore the version-matched artifact source and its exact identity',
            self::ResponseLimitExceeded => 'Reduce the admitted source result within the fixed Phase 4F limits',
            self::UnexpectedCommandFailure => 'Inspect the safe diagnostic identifier and deterministic evidence before retrying',
        };
    }
}
