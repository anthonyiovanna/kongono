<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 21G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum StorageInspectionDiagnostic: string
{
    public const array COMPONENTS = [
        'request', 'profile_set_reconstruction', 'artifact_structure',
        'current_build_binding', 'runtime_evidence', 'role_evidence',
        'graph_evidence', 'serialization', 'observation_projection',
        'security_audit_projection', 'verification',
    ];

    case InvalidInspection = 'KGN-STORAGE-INSPECTION-2151';
    case ArtifactBindingFailure = 'KGN-STORAGE-INSPECTION-2152';
    case InvalidIntelligenceEvidence = 'KGN-STORAGE-INSPECTION-2153';
    case ProjectionFailure = 'KGN-STORAGE-INSPECTION-2154';
    case VerificationFailure = 'KGN-STORAGE-INSPECTION-2155';

    public function context(string $component): DiagnosticContext
    {
        if (!in_array($component, self::COMPONENTS, true)) {
            $component = 'request';
        }

        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded storage evidence',
                'Supply exact typed in-memory evidence without disclosing object identities, content, metadata, provider data, or foreign messages',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Storage inspection request or evidence is invalid',
            self::ArtifactBindingFailure => 'Storage profile artifact or current-build binding failed',
            self::InvalidIntelligenceEvidence => 'Detached runtime, role, Graph, or intelligence evidence is invalid',
            self::ProjectionFailure => 'Storage inspection projection failed',
            self::VerificationFailure => 'Independent storage inspection verification failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact typed current storage-application build and package-owned bounded evidence values.',
            self::ArtifactBindingFailure => 'Supply one coherent structurally valid current profile set and artifact.',
            self::InvalidIntelligenceEvidence => 'Supply exact bounded detached runtime evidence, ordered role evidence, and optional matching-role Graph aggregates.',
            self::ProjectionFailure => 'Supply the validated inspection and an explicit neutral Observability context.',
            self::VerificationFailure => 'Replace the candidate with independently reconstructed untampered evidence; no repair is performed.',
        };
    }
}
