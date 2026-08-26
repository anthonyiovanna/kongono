<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed actionable non-disclosing command-build diagnostics. */
#[Api(ApiStatus::Internal)]
enum ConsoleBuildDiagnostic: string
{
    case InvalidSource = 'KGN-CONSOLE-BUILD-1001';
    case InvalidModel = 'KGN-CONSOLE-BUILD-1002';
    case InvalidArtifact = 'KGN-CONSOLE-BUILD-1003';
    case StaleArtifact = 'KGN-CONSOLE-BUILD-1004';
    case InputChanged = 'KGN-CONSOLE-BUILD-1005';

    public function context(string $failureCategory, bool $artifactCurrent = false): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [
                new DiagnosticFieldSchema('schema_version', 'Schema version', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('artifact_current', 'Artifact current', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
            ],
        ), [
            'schema_version' => 1,
            'failure_category' => $failureCategory,
            'artifact_current' => $artifactCurrent,
        ]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Validate command source',
            self::InvalidModel => 'Compile command model',
            self::InvalidArtifact => 'Validate command artifact',
            self::StaleArtifact => 'Match command artifact to current source',
            self::InputChanged => 'Authenticate stable command input',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidSource => 'The explicit source must satisfy the closed bounded version-1 vocabulary',
            self::InvalidModel => 'The compiled model must use only invariant-owning Console values',
            self::InvalidArtifact => 'The command artifact must be canonical bounded and internally consistent',
            self::StaleArtifact => 'The artifact must equal a complete build from the current source',
            self::InputChanged => 'Authenticated source bytes must remain unchanged throughout the build',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidSource => 'Invalid command source',
            self::InvalidModel => 'Invalid command model',
            self::InvalidArtifact => 'Invalid command artifact',
            self::StaleArtifact => 'Stale command artifact',
            self::InputChanged => 'Command build input changed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Correct the explicit command source and rebuild',
            self::InvalidModel => 'Correct the command definitions and rebuild through Console invariant owners',
            self::InvalidArtifact => 'Regenerate the artifact with the supported command builder',
            self::StaleArtifact => 'Build a new artifact from the exact current source',
            self::InputChanged => 'Stop concurrent mutation and rebuild from stable source bytes',
        };
    }
}
