<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns bounded non-disclosing metadata for Composition Graph projection failures. */
#[Api(ApiStatus::Internal)]
enum CompositionGraphDiagnostic: string
{
    case InvalidArtifact = 'KGN-GRAPH-1111';
    case UnsupportedVersion = 'KGN-GRAPH-1112';
    case InvalidModel = 'KGN-GRAPH-1113';
    case ProjectionConflict = 'KGN-GRAPH-1114';
    case LimitExceeded = 'KGN-GRAPH-1115';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('record_kind', 'Record kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('count', 'Count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('position', 'Position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
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
            self::InvalidArtifact => 'Composition artifact is invalid for Graph projection',
            self::UnsupportedVersion => 'Composition version is unsupported for Graph projection',
            self::InvalidModel => 'Composition model is invalid for Graph projection',
            self::ProjectionConflict => 'Composition Graph projection contains a conflict',
            self::LimitExceeded => 'Composition Graph projection limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidArtifact => 'Supply one canonical in-memory artifact produced by the accepted Composition builder contract',
            self::UnsupportedVersion => 'Rebuild the application with the exact supported kongono.composition version 2 contract',
            self::InvalidModel => 'Supply a complete canonical Composition v2 model whose references resolve inside the model',
            self::ProjectionConflict => 'Supply Composition facts that derive unique canonical Graph identities and records',
            self::LimitExceeded => 'Reduce the Composition artifact or model to the documented projection bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidArtifact => 'Admit Composition artifact',
            self::UnsupportedVersion => 'Validate Composition producer version',
            self::InvalidModel => 'Validate Composition projection model',
            self::ProjectionConflict => 'Construct Composition Graph records',
            self::LimitExceeded => 'Validate Composition projection bounds',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidArtifact => 'Artifact bytes and embedded identities must be canonical and exact',
            self::UnsupportedVersion => 'Only the accepted Composition version 2 producer contract is supported',
            self::InvalidModel => 'The complete model must use exact bounded shapes and resolved references',
            self::ProjectionConflict => 'One semantic Graph fact must derive one collision-free record identity',
            self::LimitExceeded => 'Projection input must remain within fixed documented bounds',
        };
    }
}
