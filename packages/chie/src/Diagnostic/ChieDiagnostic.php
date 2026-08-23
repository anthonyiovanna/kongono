<?php

declare(strict_types=1);

namespace Kongono\Chie\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the eighteen closed, Safe diagnostic contracts for read-only Chie. */
#[Api(ApiStatus::Internal)]
enum ChieDiagnostic: string
{
    case InvalidRequest = 'KGN-CHIE-1001';
    case ArtifactUnavailable = 'KGN-CHIE-1002';
    case ArtifactStale = 'KGN-CHIE-1003';
    case InvalidArtifact = 'KGN-CHIE-1004';
    case UnsupportedTopic = 'KGN-CHIE-1005';
    case UnsupportedDiagnostic = 'KGN-CHIE-1006';
    case CitationResolutionFailed = 'KGN-CHIE-1007';
    case SourceSectionInvalid = 'KGN-CHIE-1008';
    case ResponseLimitExceeded = 'KGN-CHIE-1009';
    case UnexpectedCommandFailure = 'KGN-CHIE-1010';
    case InvalidProposalRequest = 'KGN-CHIE-1011';
    case ProposalSpecUnavailable = 'KGN-CHIE-1012';
    case ProposalSpecInvalid = 'KGN-CHIE-1013';
    case ProposalTargetUnsupported = 'KGN-CHIE-1014';
    case ProposalTargetStale = 'KGN-CHIE-1015';
    case ProposalReplacementInvalid = 'KGN-CHIE-1016';
    case ProposalDiffLimitExceeded = 'KGN-CHIE-1017';
    case ProposalConstructionFailed = 'KGN-CHIE-1018';

    public function context(string $violation): DiagnosticContext
    {
        $valid = in_array($violation, match ($this) {
            self::InvalidRequest => ['operation', 'topic-grammar', 'diagnostic-grammar', 'claim-class', 'claim-text', 'claim-citation', 'failure-status', 'invalid-command-usage'],
            self::ArtifactUnavailable => ['artifact-path', 'artifact-read', 'framework-root', 'source-unavailable'],
            self::ArtifactStale => ['source-state-mismatch'],
            self::InvalidArtifact => ['artifact-shape', 'artifact-name', 'artifact-metadata', 'topic-catalog'],
            self::UnsupportedTopic => ['topic-unsupported'],
            self::UnsupportedDiagnostic => ['diagnostic-not-found'],
            self::CitationResolutionFailed => ['source-missing', 'authority', 'citation'],
            self::SourceSectionInvalid => ['heading-missing', 'heading-duplicate', 'section-limit'],
            self::ResponseLimitExceeded => ['artifact-limit', 'citation-limit', 'response-limit', 'line-limit', 'occurrence-limit'],
            self::UnexpectedCommandFailure => ['unexpected-failure'],
            self::InvalidProposalRequest => ['target-path', 'target-sha', 'before-shape', 'after-shape'],
            self::ProposalSpecUnavailable => ['spec-path', 'spec-read'],
            self::ProposalSpecInvalid => ['spec-path', 'spec-shape', 'before-shape', 'after-shape'],
            self::ProposalTargetUnsupported => ['target-path', 'target-authority', 'target-missing'],
            self::ProposalTargetStale => ['target-sha'],
            self::ProposalReplacementInvalid => ['match-count', 'source-limit'],
            self::ProposalDiffLimitExceeded => ['diff-limit'],
            self::ProposalConstructionFailed => ['response-limit', 'proposal-shape'],
        }, true);
        if (!$valid) {
            throw InvalidChieValueException::diagnosticViolation();
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
            self::InvalidRequest => 'Admit one read-only Chie request',
            self::ArtifactUnavailable => 'Read the explicit framework knowledge artifact',
            self::ArtifactStale => 'Match the artifact to the explicit framework root',
            self::InvalidArtifact => 'Validate the framework knowledge artifact',
            self::UnsupportedTopic => 'Resolve one closed explanation topic',
            self::UnsupportedDiagnostic => 'Locate one supplied Safe diagnostic identifier',
            self::CitationResolutionFailed => 'Resolve one exact artifact citation',
            self::SourceSectionInvalid => 'Extract one exact documented topic section',
            self::ResponseLimitExceeded => 'Enforce bounded Chie output',
            self::UnexpectedCommandFailure => 'Run the read-only Chie command',
            self::InvalidProposalRequest => 'Admit one explicit Chie proposal request',
            self::ProposalSpecUnavailable => 'Read the explicit Chie proposal specification',
            self::ProposalSpecInvalid => 'Validate the canonical Chie proposal specification',
            self::ProposalTargetUnsupported => 'Resolve one current explanatory documentation target',
            self::ProposalTargetStale => 'Match the proposal base to the artifact source identity',
            self::ProposalReplacementInvalid => 'Resolve one exact complete-line replacement',
            self::ProposalDiffLimitExceeded => 'Build one bounded reversible unified diff',
            self::ProposalConstructionFailed => 'Construct one internally consistent proposal response',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidRequest => 'The request must match one exact read-only operation and admitted Safe grammar',
            self::ArtifactUnavailable => 'The explicit local artifact and framework sources must be safely readable',
            self::ArtifactStale => 'Every answer must use an artifact equal to the exact current framework source state',
            self::InvalidArtifact => 'Artifact bytes and metadata must be canonical, internally valid, and untampered',
            self::UnsupportedTopic => 'Explanation may resolve only one of the six closed topic definitions',
            self::UnsupportedDiagnostic => 'Diagnosis may report only exact bounded occurrences in the validated artifact',
            self::CitationResolutionFailed => 'Every framework-fact claim must bind to one exact source identity and line range',
            self::SourceSectionInvalid => 'A configured heading must resolve exactly once to one complete bounded section',
            self::ResponseLimitExceeded => 'Chie must fail closed before any partial or truncated response is returned',
            self::UnexpectedCommandFailure => 'Unexpected failure must remain bounded and must not disclose native or rejected data',
            self::InvalidProposalRequest => 'The request must use the one closed proposal rule and admitted Safe value shapes',
            self::ProposalSpecUnavailable => 'The explicit ordinary proposal specification must be safely and stably readable',
            self::ProposalSpecInvalid => 'Proposal specification bytes must match the exact canonical closed schema',
            self::ProposalTargetUnsupported => 'A proposal target must resolve exactly once to current explanatory documentation',
            self::ProposalTargetStale => 'The asserted base identity must equal the exact current artifact source identity',
            self::ProposalReplacementInvalid => 'The asserted complete-line sequence must occur exactly once in the bounded source',
            self::ProposalDiffLimitExceeded => 'Forward and reverse review diffs must remain canonical and within their fixed limit',
            self::ProposalConstructionFailed => 'The candidate, provenance, handoff, warnings, and identities must be consistent',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Use a valid read-only Chie request',
            self::ArtifactUnavailable => 'Provide the explicit framework knowledge artifact',
            self::ArtifactStale => 'Regenerate the framework knowledge artifact',
            self::InvalidArtifact => 'Validate the framework knowledge artifact',
            self::UnsupportedTopic => 'Use a supported Chie topic',
            self::UnsupportedDiagnostic => 'Use an identifier present in the exact framework state',
            self::CitationResolutionFailed => 'Restore the exact cited knowledge source',
            self::SourceSectionInvalid => 'Restore the exact documented topic section',
            self::ResponseLimitExceeded => 'Reduce the bounded Chie response',
            self::UnexpectedCommandFailure => 'Inspect the Chie command boundary',
            self::InvalidProposalRequest => 'Use a valid explicit Chie proposal request',
            self::ProposalSpecUnavailable => 'Provide the explicit proposal specification',
            self::ProposalSpecInvalid => 'Use the canonical proposal specification grammar',
            self::ProposalTargetUnsupported => 'Use a current explanatory documentation target',
            self::ProposalTargetStale => 'Regenerate the request from the current artifact source',
            self::ProposalReplacementInvalid => 'Provide one uniquely matching complete-line sequence',
            self::ProposalDiffLimitExceeded => 'Reduce the bounded candidate diff',
            self::ProposalConstructionFailed => 'Restore the deterministic proposal invariants',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Use the documented explain or diagnose grammar with one admitted value',
            self::ArtifactUnavailable => 'Restore the explicit ordinary artifact and exact readable framework root',
            self::ArtifactStale => 'Generate a new artifact from the current exact framework source state',
            self::InvalidArtifact => 'Regenerate and validate the content-addressed canonical artifact',
            self::UnsupportedTopic => 'Select one token from the closed Phase 4D topic catalog',
            self::UnsupportedDiagnostic => 'Supply an admitted identifier that occurs in the exact validated sources',
            self::CitationResolutionFailed => 'Restore the version-matched artifact source and its exact identity',
            self::SourceSectionInvalid => 'Restore the unique configured heading and keep its section within bounds',
            self::ResponseLimitExceeded => 'Reduce the admitted source result within the fixed Phase 4D limits',
            self::UnexpectedCommandFailure => 'Inspect the safe diagnostic identifier and deterministic evidence before retrying',
            self::InvalidProposalRequest => 'Use the documented exact-line proposal grammar with bounded caller-asserted Safe text',
            self::ProposalSpecUnavailable => 'Restore the explicit ordinary file and retry the stable bounded read',
            self::ProposalSpecInvalid => 'Recreate the compact ordered JSON specification with exact admitted values',
            self::ProposalTargetUnsupported => 'Select exactly one artifact source classified as current explanatory documentation',
            self::ProposalTargetStale => 'Use the uppercase SHA-256 of the exact current artifact source',
            self::ProposalReplacementInvalid => 'Use complete lines that occur exactly once in the exact bounded source',
            self::ProposalDiffLimitExceeded => 'Reduce the admitted replacement so both review diffs fit the fixed bound',
            self::ProposalConstructionFailed => 'Reconstruct the candidate from the exact validated artifact and closed rule',
        };
    }
}
