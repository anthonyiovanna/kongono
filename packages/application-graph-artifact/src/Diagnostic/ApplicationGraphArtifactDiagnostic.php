<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Graph artifact diagnostic contract. */
#[Api(ApiStatus::Internal)]
enum ApplicationGraphArtifactDiagnostic: string
{
    case InvalidGraphInput = 'KGN-GRAPH-1116';
    case InvalidArtifact = 'KGN-GRAPH-1117';
    case ArtifactLimitExceeded = 'KGN-GRAPH-1118';
    case StaleArtifact = 'KGN-GRAPH-1119';
    case PublicationFailed = 'KGN-GRAPH-1120';
    case LoadFailed = 'KGN-GRAPH-1121';

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        $fields = [];
        foreach ($values as $key => $_) {
            $fields[] = new DiagnosticFieldSchema(
                $key,
                match ($key) {
                    'violation' => 'Violation', 'operation' => 'Operation', 'record_kind' => 'Record kind', 'count' => 'Count', 'position' => 'Position', default => 'Value',
                },
                in_array($key, ['count', 'position'], true) ? DiagnosticValueKind::Integer : DiagnosticValueKind::Text,
                DiagnosticDisclosure::Safe,
            );
        }

        return new DiagnosticContext(new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidGraphInput => 'Application Graph input is invalid',
            self::InvalidArtifact => 'Application Graph artifact is invalid',
            self::ArtifactLimitExceeded => 'Application Graph artifact limit is exceeded',
            self::StaleArtifact => 'Application Graph artifact is stale',
            self::PublicationFailed => 'Application Graph artifact publication failed',
            self::LoadFailed => 'Application Graph artifact loading failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidGraphInput => 'Supply one complete Graph that can be re-admitted through the supported builder',
            self::InvalidArtifact => 'Supply exact canonical version-1 Graph artifact bytes',
            self::ArtifactLimitExceeded => 'Reduce the Graph or artifact within the documented fixed bounds',
            self::StaleArtifact => 'Build a fresh artifact from the caller-supplied current Graph',
            self::PublicationFailed => 'Use one canonical existing ordinary directory without a conflicting target',
            self::LoadFailed => 'Supply one canonical explicit ordinary content-addressed artifact file',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidGraphInput => 'Build Application Graph artifact',
            self::InvalidArtifact => 'Validate Application Graph artifact',
            self::ArtifactLimitExceeded => 'Enforce Application Graph artifact bounds',
            self::StaleArtifact => 'Validate Application Graph artifact currentness',
            self::PublicationFailed => 'Publish Application Graph artifact',
            self::LoadFailed => 'Load Application Graph artifact',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidGraphInput => 'Supplied Graph facts must pass fresh typed admission',
            self::InvalidArtifact => 'Artifact bytes and all derived identities must be canonical and exact',
            self::ArtifactLimitExceeded => 'Artifact work must remain within fixed documented limits',
            self::StaleArtifact => 'Artifact bytes must equal fresh derivation from the supplied current Graph',
            self::PublicationFailed => 'Publication must be atomic, content-addressed, initial-only, and no-clobber',
            self::LoadFailed => 'Loading must admit one stable explicitly named ordinary artifact file',
        };
    }
}
