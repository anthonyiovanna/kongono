<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Phase 19F diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum AssetApplicationDiagnostic: string
{
    case InvalidDefinition = 'KGN-ASSET-APPLICATION-1940';
    case UnsafeSource = 'KGN-ASSET-APPLICATION-1941';
    case WorkflowFailed = 'KGN-ASSET-APPLICATION-1942';
    case AdapterFailed = 'KGN-ASSET-APPLICATION-1943';
    case ResolutionFailed = 'KGN-ASSET-APPLICATION-1944';
    case OperationMismatch = 'KGN-ASSET-APPLICATION-1945';

    public function context(string $value): DiagnosticContext
    {
        $name = in_array($this, [self::InvalidDefinition, self::UnsafeSource], true) ? 'violation' : 'stage';

        return new DiagnosticContext(
            new DiagnosticContextSchema(
                $this->operation(),
                $this->invariant(),
                [new DiagnosticFieldSchema($name, ucfirst($name), DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            [$name => $value],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Asset Application definition is invalid',
            self::UnsafeSource => 'Asset Application source evidence is unsafe or unstable',
            self::WorkflowFailed => 'Asset Application workflow failed',
            self::AdapterFailed => 'Asset Application adapter operation failed',
            self::ResolutionFailed => 'Asset Application resolution failed',
            self::OperationMismatch => 'Asset Application operation does not match its evidence',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Supply one closed Version 1 definition beneath one canonical application root',
            self::UnsafeSource => 'Supply unchanged ordinary bounded files beneath the canonical application root',
            self::WorkflowFailed => 'Correct the explicit source or exact content-addressed publication evidence',
            self::AdapterFailed => 'Supply one matching neutral adapter request and completely validated result',
            self::ResolutionFailed => 'Check exact-current evidence and supply one explicit resolution policy',
            self::OperationMismatch => 'Use the matching explicit operation and evidence identity',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Bind Asset Application definition',
            self::UnsafeSource => 'Read Asset Application source',
            self::WorkflowFailed => 'Plan build or check Asset Application evidence',
            self::AdapterFailed => 'Run supplied Asset Application adapter',
            self::ResolutionFailed => 'Resolve exact-current Asset Application evidence',
            self::OperationMismatch => 'Match Asset Application operation',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Source definition and paths must be closed explicit canonical and bounded',
            self::UnsafeSource => 'Every source read must remain contained ordinary stable and exact',
            self::WorkflowFailed => 'Workflow effects must be explicit deterministic exact-current and no-clobber',
            self::AdapterFailed => 'Adapter evidence must match its explicit request and validate completely',
            self::ResolutionFailed => 'Resolution must use one checked artifact identity and explicit policy',
            self::OperationMismatch => 'Operation intent must match the supplied evidence',
        };
    }
}
