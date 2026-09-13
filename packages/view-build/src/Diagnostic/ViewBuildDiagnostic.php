<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed bounded diagnostic vocabulary for deterministic view builds. */
#[Api(ApiStatus::Internal)]
enum ViewBuildDiagnostic: string
{
    case InvalidSource = 'KGN-VIEW-BUILD-1810';
    case UnsupportedGrammar = 'KGN-VIEW-BUILD-1811';
    case BoundExceeded = 'KGN-VIEW-BUILD-1812';
    case InvalidArtifact = 'KGN-VIEW-BUILD-1813';
    case StaleArtifact = 'KGN-VIEW-BUILD-1814';
    case PublicationFailed = 'KGN-VIEW-BUILD-1815';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('operation_index', 'Operation index', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('depth', 'Depth', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('version', 'Version', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('node_kind', 'Node kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('output_context', 'Output context', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('view_identity', 'View identity', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
            new DiagnosticFieldSchema('source_pointer', 'Source pointer', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
            new DiagnosticFieldSchema('sha256', 'SHA-256', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidSource => 'Structured view source is invalid',
            self::UnsupportedGrammar => 'View grammar or output context is unsupported',
            self::BoundExceeded => 'View build bound is exceeded',
            self::InvalidArtifact => 'Compiled view artifact is invalid',
            self::StaleArtifact => 'Compiled view artifact is stale',
            self::PublicationFailed => 'Compiled view artifact publication failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Supply one complete canonical version-1 structured source byte sequence',
            self::UnsupportedGrammar => 'Use only the documented closed schema, node, element, attribute, and reference vocabulary',
            self::BoundExceeded => 'Reduce the source, model, operation, or generated evidence to the documented bound',
            self::InvalidArtifact => 'Supply byte-identical canonical metadata and generated PHP from this builder version',
            self::StaleArtifact => 'Rebuild from the explicitly supplied current source set',
            self::PublicationFailed => 'Use one explicit safe writable directory and an absent or byte-identical target',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Parse structured view source',
            self::UnsupportedGrammar => 'Compile structured view grammar',
            self::BoundExceeded => 'Enforce deterministic build bounds',
            self::InvalidArtifact => 'Validate compiled view structure',
            self::StaleArtifact => 'Validate compiled view currentness',
            self::PublicationFailed => 'Publish compiled view artifact',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidSource => 'Source framing, decoding, types, uniqueness, and structure must be exact',
            self::UnsupportedGrammar => 'Only compile-known HTML text and quoted-attribute contexts are admitted',
            self::BoundExceeded => 'All source, schema, node, operation, model, artifact, and generated-byte bounds are finite',
            self::InvalidArtifact => 'Metadata and generated PHP must be canonical, internally consistent, and reproducible',
            self::StaleArtifact => 'All current source, model, generated, and artifact identities must agree',
            self::PublicationFailed => 'Publication is contained, atomic, initial-only, idempotent for identical bytes, and no-clobber',
        };
    }
}
