<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Vite-adapter diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum ViteAdapterDiagnostic: string
{
    case InvalidToolchain = 'KGN-ASSET-VITE-1926';
    case WorkspaceFailure = 'KGN-ASSET-VITE-1927';
    case ProcessFailure = 'KGN-ASSET-VITE-1928';
    case InvalidManifest = 'KGN-ASSET-VITE-1929';
    case InvalidOutput = 'KGN-ASSET-VITE-1930';
    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),new DiagnosticFieldSchema('cleanup_status', 'Cleanup status', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),new DiagnosticFieldSchema('evidence_sha256', 'Evidence SHA-256', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false)]);
    }
    /** @param array<string,string|int|bool> $values */public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }public function title(): string
    {
        return match ($this) {
            self::InvalidToolchain => 'Vite toolchain evidence is invalid',self::WorkspaceFailure => 'Vite workspace operation failed',self::ProcessFailure => 'Vite build process failed',self::InvalidManifest => 'Vite manifest is invalid',self::InvalidOutput => 'Vite output is invalid or incomplete',
        };
    }public function remediation(): string
    {
        return match ($this) {
            self::InvalidToolchain => 'Supply unchanged explicit ordinary Node and Vite launch files',self::WorkspaceFailure => 'Supply a writable canonical parent and inspect protected local workspace evidence',self::ProcessFailure => 'Inspect protected process evidence and correct the external build',self::InvalidManifest => 'Produce the closed bounded Version 1 manifest shape',self::InvalidOutput => 'Produce the exact complete contained output inventory',
        };
    }private function operation(): string
    {
        return match ($this) {
            self::InvalidToolchain => 'Authenticate Vite toolchain',self::WorkspaceFailure => 'Manage Vite workspace',self::ProcessFailure => 'Run Vite build',self::InvalidManifest => 'Decode Vite manifest',self::InvalidOutput => 'Validate Vite output',
        };
    }private function invariant(): string
    {
        return match ($this) {
            self::InvalidToolchain => 'Launch paths and hashes must remain explicit and exact',self::WorkspaceFailure => 'Only one contained operation-owned workspace may be changed',self::ProcessFailure => 'One bounded direct process must complete successfully',self::InvalidManifest => 'Producer metadata must remain duplicate-safe closed and bounded',self::InvalidOutput => 'Every emitted byte must be contained complete bounded and content-bound',
        };
    }
}
