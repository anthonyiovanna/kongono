<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed bounded diagnostic vocabulary for compiled view execution. */
#[Api(ApiStatus::Internal)]
enum ViewRuntimeDiagnostic: string
{
    case InvalidArtifact = 'KGN-VIEW-RUNTIME-1830';
    case StaleArtifact = 'KGN-VIEW-RUNTIME-1831';
    case ArtifactLoadFailed = 'KGN-VIEW-RUNTIME-1832';
    case InvalidRequest = 'KGN-VIEW-RUNTIME-1833';
    case UnsupportedOutputContext = 'KGN-VIEW-RUNTIME-1834';
    case BoundExceeded = 'KGN-VIEW-RUNTIME-1835';
    case RenderingFailed = 'KGN-VIEW-RUNTIME-1836';
    case RetirementFailed = 'KGN-VIEW-RUNTIME-1837';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('observed_count', 'Observed count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('operation_index', 'Operation index', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('output_context', 'Output context', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('primary_failure_kind', 'Primary failure kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('view_identity', 'View identity', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
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
            self::InvalidArtifact => 'Compiled view artifact evidence is invalid',
            self::StaleArtifact => 'Compiled view artifact is not current',
            self::ArtifactLoadFailed => 'Compiled view program could not be loaded',
            self::InvalidRequest => 'Render request is inconsistent',
            self::UnsupportedOutputContext => 'Compiled output context is unsupported',
            self::BoundExceeded => 'Render bound is exceeded',
            self::RenderingFailed => 'Compiled view rendering failed',
            self::RetirementFailed => 'Render operation retirement failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidArtifact => 'Supply one canonical contained Phase 18C artifact with exact evidence relations',
            self::StaleArtifact => 'Select the exact compiled identity owned by the current application deployment',
            self::ArtifactLoadFailed => 'Deploy the authenticated generated program in an unambiguous process class table',
            self::InvalidRequest => 'Supply a version-1 request whose view, schema, context, and artifact identities agree',
            self::UnsupportedOutputContext => 'Use only compiler-approved HTML text or quoted-attribute output contexts',
            self::BoundExceeded => 'Increase the explicit finite limit or reduce rendered output and execution steps',
            self::RenderingFailed => 'Correct the selected compiled program or its explicitly supplied presentation values',
            self::RetirementFailed => 'Discard the operation and investigate local runtime cleanup',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidArtifact => 'Authenticate compiled view artifact evidence',
            self::StaleArtifact => 'Validate caller-selected runtime currentness',
            self::ArtifactLoadFailed => 'Load authenticated generated view program',
            self::InvalidRequest => 'Validate compiled render request relation',
            self::UnsupportedOutputContext => 'Escape dynamic presentation data',
            self::BoundExceeded => 'Enforce render byte and step limits',
            self::RenderingFailed => 'Execute compiled view program',
            self::RetirementFailed => 'Retire render operation state',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidArtifact => 'Only stable canonical fixed-name contained artifact evidence is admitted',
            self::StaleArtifact => 'Loaded metadata identity must equal the explicit caller-selected identity',
            self::ArtifactLoadFailed => 'Only one digest-authenticated exact generated class may execute or be reused',
            self::InvalidRequest => 'Request version, identities, schema, and context must match the loaded artifact',
            self::UnsupportedOutputContext => 'Every dynamic string is escaped in one of two closed contexts',
            self::BoundExceeded => 'Steps and bytes are checked before counters or output can exceed their limits',
            self::RenderingFailed => 'A failed generated invocation exposes no partial output or private material',
            self::RetirementFailed => 'Operation-local mutable state is cleared after every recoverable outcome',
        };
    }
}
