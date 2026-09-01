<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing metadata for bounded Agent Runtime failures. */
#[Api(ApiStatus::Internal)]
enum AgentRuntimeDiagnostic: string
{
    case InvalidInput = 'KGN-AGENT-RUNTIME-1330';
    case OperationFailed = 'KGN-AGENT-RUNTIME-1331';
    case CleanupFailed = 'KGN-AGENT-RUNTIME-1332';
    case OperationAndCleanupFailed = 'KGN-AGENT-RUNTIME-1333';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = match ($this) {
            self::InvalidInput => [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            self::OperationFailed => [new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            self::CleanupFailed => [new DiagnosticFieldSchema('cleanup_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe)],
            self::OperationAndCleanupFailed => [
                new DiagnosticFieldSchema('primary_present', 'Primary failure present', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('cleanup_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
            ],
        };

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInput => 'Agent Runtime input is invalid',
            self::OperationFailed => 'Agent Runtime operation failed',
            self::CleanupFailed => 'Agent Runtime cleanup failed',
            self::OperationAndCleanupFailed => 'Agent Runtime operation and cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Supply exact admitted current evidence, canonical ports, and bounded values',
            self::OperationFailed => 'Inspect the caller-owned operation collaborator without exposing sensitive values',
            self::CleanupFailed => 'Treat the operation as retired and inspect every preserved cleanup failure',
            self::OperationAndCleanupFailed => 'Preserve the primary failure and inspect every cleanup failure before retrying externally',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Validate Agent Runtime input',
            self::OperationFailed => 'Run bounded Agent Runtime operation',
            self::CleanupFailed => 'Retire Agent Runtime ports',
            self::OperationAndCleanupFailed => 'Compose Agent Runtime lifecycle failures',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidInput => 'Runtime input must remain exact, typed, canonical, admitted, and declaration-bounded',
            self::OperationFailed => 'Collaborator failure must remain visible and must never grant permission',
            self::CleanupFailed => 'Every accepted operation port receives exactly one close attempt',
            self::OperationAndCleanupFailed => 'Primary and cleanup failures remain separately inspectable',
        };
    }
}
