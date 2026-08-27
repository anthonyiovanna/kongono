<?php

declare(strict_types=1);

namespace Kongono\Composition\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed, non-disclosing Phase 5D diagnostic contract. */
#[Api(ApiStatus::Internal)]
enum CompositionDiagnostic: string
{
    case InvalidApplicationRoot = 'KGN-COMPOSITION-1001';
    case UnsafeCompositionSource = 'KGN-COMPOSITION-1002';
    case InvalidSourceBytes = 'KGN-COMPOSITION-1003';
    case InvalidSourceJson = 'KGN-COMPOSITION-1004';
    case UnsupportedSourceSchema = 'KGN-COMPOSITION-1005';
    case CompositionLimitExceeded = 'KGN-COMPOSITION-1006';
    case InvalidDeclaration = 'KGN-COMPOSITION-1007';
    case InvalidCompositionGraph = 'KGN-COMPOSITION-1008';
    case InvalidProvenance = 'KGN-COMPOSITION-1009';
    case InvalidArtifact = 'KGN-COMPOSITION-1010';
    case StaleArtifact = 'KGN-COMPOSITION-1011';
    case PublicationFailed = 'KGN-COMPOSITION-1012';
    case InvalidGeneratedWiring = 'KGN-COMPOSITION-1013';
    case GeneratedWiringPublicationFailed = 'KGN-COMPOSITION-1014';
    case LifecycleFailuresCombined = 'KGN-COMPOSITION-1015';
    case CompositionBoundaryUnavailable = 'KGN-COMPOSITION-1016';
    case GeneratedWiringLoadFailed = 'KGN-COMPOSITION-1017';
    case ApplicationWorkflowFailed = 'KGN-COMPOSITION-1018';
    case InvalidGeneratedOperationInputs = 'KGN-COMPOSITION-1019';

    public function context(string $violation): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
        ), ['violation' => $violation]);
    }

    public function lifecycleContext(string $boundary, int $cleanupFailureCount): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [
                new DiagnosticFieldSchema('boundary', 'Boundary', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('cleanup_failure_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
            ],
        ), ['boundary' => $boundary, 'cleanup_failure_count' => $cleanupFailureCount]);
    }

    public function workflowContext(string $workflowStage, string $violation): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [
                new DiagnosticFieldSchema('workflow_stage', 'Workflow stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), ['workflow_stage' => $workflowStage, 'violation' => $violation]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Resolve composition application root',
            self::UnsafeCompositionSource => 'Admit composition source path',
            self::InvalidSourceBytes => 'Validate composition source bytes',
            self::InvalidSourceJson => 'Parse composition source JSON',
            self::UnsupportedSourceSchema => 'Validate composition schema and version',
            self::CompositionLimitExceeded => 'Enforce composition source bounds',
            self::InvalidDeclaration => 'Validate composition declarations',
            self::InvalidCompositionGraph => 'Validate composition graph',
            self::InvalidProvenance => 'Prove composition provenance',
            self::InvalidArtifact => 'Validate composition artifact structure',
            self::StaleArtifact => 'Match composition artifact to current state',
            self::PublicationFailed => 'Publish composition artifact',
            self::InvalidGeneratedWiring => 'Validate generated composition wiring',
            self::GeneratedWiringPublicationFailed => 'Publish generated composition wiring',
            self::LifecycleFailuresCombined => 'Complete composition lifecycle cleanup',
            self::CompositionBoundaryUnavailable => 'Use generated composition boundary',
            self::GeneratedWiringLoadFailed => 'Load current generated composition wiring',
            self::ApplicationWorkflowFailed => 'Build or check current application composition',
            self::InvalidGeneratedOperationInputs => 'Validate generated operation inputs',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'The caller must provide one ordinary resolved application root',
            self::UnsafeCompositionSource => 'Every required file must be exact-case, contained, ordinary, and free of aliases or streams',
            self::InvalidSourceBytes => 'The source must be bounded UTF-8 without BOM, CR, NUL, or invalid final-LF form',
            self::InvalidSourceJson => 'The source must be strict JSON without duplicate members or extension syntax',
            self::UnsupportedSourceSchema => 'The source must use the exact supported schema, version, and top-level members',
            self::CompositionLimitExceeded => 'Every source, declaration, edge, depth, and artifact count must remain within fixed bounds',
            self::InvalidDeclaration => 'Every declaration and inspected symbol must match one complete supported source contract',
            self::InvalidCompositionGraph => 'Bindings, roots, dependencies, input lifetimes, service lifetimes, and cycles must satisfy the declared graph contract',
            self::InvalidProvenance => 'Every project and declaring source identity must remain exact, contained, and stable',
            self::InvalidArtifact => 'The artifact must be canonical and match its exact schema and internally derived identities',
            self::StaleArtifact => 'The artifact must be byte-identical to a complete build from current application state',
            self::PublicationFailed => 'Validated bytes must become visible atomically without overwrite, conflict, or temporary residue',
            self::InvalidGeneratedWiring => 'Generated PHP must be canonical, bounded, current, and match its exact metadata and grammar',
            self::GeneratedWiringPublicationFailed => 'Validated generated PHP must publish atomically without overwrite, conflict, or residue',
            self::LifecycleFailuresCombined => 'The primary lifecycle failure and every cleanup failure must remain observable in deterministic order',
            self::CompositionBoundaryUnavailable => 'A retired, closed, or leased boundary must reject unavailable lifecycle work without cleanup',
            self::GeneratedWiringLoadFailed => 'Only one exact current contained generated assembly may be loaded and started once',
            self::ApplicationWorkflowFailed => 'Every workflow stage must produce exact current contained content-addressed output',
            self::InvalidGeneratedOperationInputs => 'Operation inputs must match the generated bounded count, order, and native contract before work begins',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Invalid composition application root',
            self::UnsafeCompositionSource => 'Unsafe composition source',
            self::InvalidSourceBytes => 'Invalid composition source bytes',
            self::InvalidSourceJson => 'Invalid composition source JSON',
            self::UnsupportedSourceSchema => 'Unsupported composition source schema',
            self::CompositionLimitExceeded => 'Composition limit exceeded',
            self::InvalidDeclaration => 'Invalid composition declaration',
            self::InvalidCompositionGraph => 'Invalid composition graph',
            self::InvalidProvenance => 'Invalid composition provenance',
            self::InvalidArtifact => 'Invalid composition artifact',
            self::StaleArtifact => 'Stale composition artifact',
            self::PublicationFailed => 'Composition artifact publication failed',
            self::InvalidGeneratedWiring => 'Invalid generated wiring',
            self::GeneratedWiringPublicationFailed => 'Generated wiring publication failed',
            self::LifecycleFailuresCombined => 'Composition lifecycle failures combined',
            self::CompositionBoundaryUnavailable => 'Composition boundary unavailable',
            self::GeneratedWiringLoadFailed => 'Generated wiring load failed',
            self::ApplicationWorkflowFailed => 'Application workflow failed',
            self::InvalidGeneratedOperationInputs => 'Invalid generated operation inputs',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Supply the exact existing application root and rerun the build',
            self::UnsafeCompositionSource => 'Use ordinary exact-case files beneath the explicit application root',
            self::InvalidSourceBytes => 'Correct the source encoding and final line ending within the fixed byte limit',
            self::InvalidSourceJson => 'Correct the strict JSON syntax and remove duplicate members',
            self::UnsupportedSourceSchema => 'Use the exact kongono.composition version-1 or version-2 top-level contract',
            self::CompositionLimitExceeded => 'Reduce the source within the fixed supported bounds',
            self::InvalidDeclaration => 'Correct the indicated declaration category before rebuilding',
            self::InvalidCompositionGraph => 'Correct bindings, roots, dependency edges, lifetimes, or cycles before rebuilding',
            self::InvalidProvenance => 'Restore exact contained project and symbol source state before rebuilding',
            self::InvalidArtifact => 'Regenerate the artifact from the exact supported builder and source state',
            self::StaleArtifact => 'Build a new content-addressed artifact from current application state',
            self::PublicationFailed => 'Use a safe pre-existing output directory and resolve the publication conflict',
            self::InvalidGeneratedWiring => 'Regenerate wiring from the exact current composition artifact and supported generator',
            self::GeneratedWiringPublicationFailed => 'Use a safe pre-existing output directory and resolve the generated-wiring publication conflict',
            self::LifecycleFailuresCombined => 'Inspect the retained primary and cleanup failures and retire uncertain external runtime state',
            self::CompositionBoundaryUnavailable => 'Close active operations or stop using the retired or closed boundary before continuing',
            self::GeneratedWiringLoadFailed => 'Restore the exact current generated file and supply its ordered typed startup inputs',
            self::ApplicationWorkflowFailed => 'Run the explicit app:build or app:check command and correct the reported workflow stage and invariant',
            self::InvalidGeneratedOperationInputs => 'Supply the exact generated count, order, and typed operation inputs and retry with a fresh operation',
        };
    }
}
