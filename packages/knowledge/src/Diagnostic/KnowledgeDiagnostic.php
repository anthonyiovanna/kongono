<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed diagnostic contract for framework-knowledge operations. */
#[Api(ApiStatus::Internal)]
enum KnowledgeDiagnostic: string
{
    case InvalidInventory = 'KGN-KNOWLEDGE-1001';
    case InvalidSourcePath = 'KGN-KNOWLEDGE-1002';
    case SourceUnavailable = 'KGN-KNOWLEDGE-1003';
    case UnsafeSource = 'KGN-KNOWLEDGE-1004';
    case SourceLimitExceeded = 'KGN-KNOWLEDGE-1005';
    case InvalidSourceEncoding = 'KGN-KNOWLEDGE-1006';
    case InvalidArtifact = 'KGN-KNOWLEDGE-1007';
    case StaleArtifact = 'KGN-KNOWLEDGE-1008';
    case PublicationFailed = 'KGN-KNOWLEDGE-1009';
    case InvalidCommand = 'KGN-KNOWLEDGE-1010';
    case UnexpectedCommandFailure = 'KGN-KNOWLEDGE-1011';

    public function context(string $violation): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
        ), ['violation' => $violation]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidInventory => 'Load framework knowledge source inventory',
            self::InvalidSourcePath => 'Admit framework knowledge source path',
            self::SourceUnavailable => 'Read framework knowledge source',
            self::UnsafeSource => 'Validate framework knowledge source type',
            self::SourceLimitExceeded => 'Enforce framework knowledge bounds',
            self::InvalidSourceEncoding => 'Validate framework knowledge source bytes',
            self::InvalidArtifact => 'Validate framework knowledge artifact',
            self::StaleArtifact => 'Match framework knowledge artifact to current sources',
            self::PublicationFailed => 'Publish framework knowledge artifact',
            self::InvalidCommand => 'Invoke framework knowledge command',
            self::UnexpectedCommandFailure => 'Run framework knowledge command',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidInventory => 'The inventory must be canonical, closed, ordered, unique, and within bounds',
            self::InvalidSourcePath => 'Every source path must be relative, contained, exact, and free of aliases or stream syntax',
            self::SourceUnavailable => 'Every required source must remain available and unchanged for the complete bounded read',
            self::UnsafeSource => 'Every admitted source must be an authorized ordinary regular file',
            self::SourceLimitExceeded => 'Inventory, path, source, implementation, and artifact work must remain within fixed limits',
            self::InvalidSourceEncoding => 'Every included source must be UTF-8 without BOM, CR, NUL, or invalid final-LF form',
            self::InvalidArtifact => 'The artifact must match the exact canonical schema and all internally derivable identities',
            self::StaleArtifact => 'The artifact must equal complete regeneration from the exact current source, inventory, contract, and generator state',
            self::PublicationFailed => 'A complete validated artifact must become visible atomically without overwrite or partial output',
            self::InvalidCommand => 'The command must match one exact non-interactive operation and argument contract',
            self::UnexpectedCommandFailure => 'Unexpected native failure must remain bounded and must not disclose rejected values',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInventory => 'Invalid framework knowledge source inventory',
            self::InvalidSourcePath => 'Invalid framework knowledge source path',
            self::SourceUnavailable => 'Framework knowledge source is unavailable',
            self::UnsafeSource => 'Unsafe framework knowledge source',
            self::SourceLimitExceeded => 'Framework knowledge limit exceeded',
            self::InvalidSourceEncoding => 'Invalid framework knowledge source encoding',
            self::InvalidArtifact => 'Invalid framework knowledge artifact',
            self::StaleArtifact => 'Stale framework knowledge artifact',
            self::PublicationFailed => 'Framework knowledge artifact publication failed',
            self::InvalidCommand => 'Invalid framework knowledge command',
            self::UnexpectedCommandFailure => 'Unexpected framework knowledge command failure',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInventory => 'Correct the canonical closed inventory before generating an artifact',
            self::InvalidSourcePath => 'Use one exact allowlisted regular-file path beneath the explicit framework root',
            self::SourceUnavailable => 'Restore the exact required source state and rerun generation',
            self::UnsafeSource => 'Remove the unsafe indirection or source and use the authorized ordinary file',
            self::SourceLimitExceeded => 'Reduce the authorized input within the fixed Phase 4C bounds',
            self::InvalidSourceEncoding => 'Correct the authorized source encoding without changing its intended meaning',
            self::InvalidArtifact => 'Regenerate the artifact from the exact authorized sources and generator',
            self::StaleArtifact => 'Generate a new content-addressed artifact from the current exact source state',
            self::PublicationFailed => 'Use an empty compatible output directory and retry explicit publication',
            self::InvalidCommand => 'Use the documented check, generate, or validate command form',
            self::UnexpectedCommandFailure => 'Inspect the diagnostic identifier and deterministic evidence before retrying',
        };
    }
}
