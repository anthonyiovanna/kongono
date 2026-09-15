<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing adapter-contract diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum AssetAdapterContractDiagnostic: string
{
    case InvalidRequest = 'KGN-ASSET-ADAPTER-1921';
    case InvalidProcess = 'KGN-ASSET-ADAPTER-1922';
    case InvalidOutput = 'KGN-ASSET-ADAPTER-1923';
    case InvalidArtifact = 'KGN-ASSET-ADAPTER-1924';
    case ExecutionFailed = 'KGN-ASSET-ADAPTER-1925';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('evidence_sha256', 'Evidence SHA-256', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
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
            self::InvalidRequest => 'Asset adapter request evidence is invalid',
            self::InvalidProcess => 'Asset adapter process evidence is invalid',
            self::InvalidOutput => 'Asset adapter output evidence is invalid',
            self::InvalidArtifact => 'Asset adapter artifact is invalid or stale',
            self::ExecutionFailed => 'Asset adapter execution failed',
        };
    }
    public function remediation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Supply the documented exact profile, toolchain, source set, and finite limits',
            self::InvalidProcess => 'Supply a bounded argument-vector command and complete lifecycle evidence',
            self::InvalidOutput => 'Supply one complete canonical reachable output graph',
            self::InvalidArtifact => 'Rebuild and validate one exact-current canonical adapter artifact',
            self::ExecutionFailed => 'Inspect protected local evidence and correct the external adapter operation',
        };
    }
    private function operation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Validate asset adapter request', self::InvalidProcess => 'Validate build process evidence', self::InvalidOutput => 'Validate translated output evidence', self::InvalidArtifact => 'Validate adapter artifact', self::ExecutionFailed => 'Execute asset adapter build',
        };
    }
    private function invariant(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Adapter inputs must remain exact, bounded, immutable evidence', self::InvalidProcess => 'One direct process must preserve exact bounded lifecycle truth', self::InvalidOutput => 'Outputs must be complete, canonical, reachable, acyclic, and content-bound', self::InvalidArtifact => 'Artifact bytes must be canonical and exact-current to all supplied evidence', self::ExecutionFailed => 'Execution failures must remain terminal and nondisclosing',
        };
    }
}
