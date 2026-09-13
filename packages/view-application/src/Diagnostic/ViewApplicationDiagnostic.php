<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Phase 18F diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum ViewApplicationDiagnostic: string
{
    case InvalidDefinition = 'KGN-VIEW-APPLICATION-1850';
    case UnsafeSource = 'KGN-VIEW-APPLICATION-1851';
    case WorkflowFailed = 'KGN-VIEW-APPLICATION-1852';
    case LoadFailed = 'KGN-VIEW-APPLICATION-1853';
    case OperationFailed = 'KGN-VIEW-APPLICATION-1854';

    public function context(string $value): DiagnosticContext
    {
        $name = self::InvalidDefinition === $this || self::UnsafeSource === $this ? 'violation' : 'stage';

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
            self::InvalidDefinition => 'View Application definition or profile is invalid',
            self::UnsafeSource => 'View Application source evidence is unsafe or unstable',
            self::WorkflowFailed => 'View Application workflow failed',
            self::LoadFailed => 'View Application artifact load or currentness failed',
            self::OperationFailed => 'View Application render or response operation failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Supply one explicit canonical contained source and output definition',
            self::UnsafeSource => 'Supply one unchanged ordinary bounded source file under the application root',
            self::WorkflowFailed => 'Inspect the bounded stage and preserve any conflicting artifact',
            self::LoadFailed => 'Check exact current artifact evidence before loading one explicit artifact',
            self::OperationFailed => 'Use a request bound to the loaded compiled identity and explicit response policy',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Bind View Application definition',
            self::UnsafeSource => 'Read View Application source',
            self::WorkflowFailed => 'Plan build or check View Application artifact',
            self::LoadFailed => 'Load View Application artifact',
            self::OperationFailed => 'Run View Application operation',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Definition and profile evidence must be explicit canonical and exact',
            self::UnsafeSource => 'Source evidence must be contained ordinary bounded and stable',
            self::WorkflowFailed => 'Workflow effects must be explicit deterministic current and no-clobber',
            self::LoadFailed => 'Loading must authenticate one exact-current content-addressed artifact',
            self::OperationFailed => 'Rendering and response adaptation must preserve lower-layer meaning',
        };
    }
}
