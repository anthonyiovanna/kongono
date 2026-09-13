<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for view contracts. */
#[Api(ApiStatus::Internal)]
enum ViewContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-VIEW-CONTRACT-1801';
    case InvalidPresentationValue = 'KGN-VIEW-CONTRACT-1802';
    case InvalidPresentationSchema = 'KGN-VIEW-CONTRACT-1803';
    case ContextSchemaMismatch = 'KGN-VIEW-CONTRACT-1804';
    case InvalidRenderLimits = 'KGN-VIEW-CONTRACT-1805';
    case InvalidRenderRequest = 'KGN-VIEW-CONTRACT-1806';
    case InvalidRenderResult = 'KGN-VIEW-CONTRACT-1807';
    case InvalidRenderFailure = 'KGN-VIEW-CONTRACT-1808';
    case ContractBoundExceeded = 'KGN-VIEW-CONTRACT-1809';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('depth', 'Depth', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('value_kind', 'Value kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('state', 'State', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'View contract identity is invalid',
            self::InvalidPresentationValue => 'Presentation value is invalid',
            self::InvalidPresentationSchema => 'Presentation schema is invalid',
            self::ContextSchemaMismatch => 'View context does not match its schema',
            self::InvalidRenderLimits => 'Render limits are invalid',
            self::InvalidRenderRequest => 'Render request is invalid',
            self::InvalidRenderResult => 'Render result is invalid',
            self::InvalidRenderFailure => 'Render failure evidence is invalid',
            self::ContractBoundExceeded => 'View contract bound is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply the documented bounded canonical identity',
            self::InvalidPresentationValue => 'Supply only a bounded closed presentation value',
            self::InvalidPresentationSchema => 'Supply one bounded closed recursive presentation schema',
            self::ContextSchemaMismatch => 'Supply a closed context whose fields and values match the declared schema',
            self::InvalidRenderLimits => 'Supply explicit render limits within the documented ranges',
            self::InvalidRenderRequest => 'Supply a complete request with matching typed context evidence',
            self::InvalidRenderResult => 'Supply completed output and derived metadata whose relations agree',
            self::InvalidRenderFailure => 'Supply closed failed-state evidence with the exact required identities',
            self::ContractBoundExceeded => 'Reduce the supplied value or collection to the documented bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create view contract identity',
            self::InvalidPresentationValue => 'Create presentation value',
            self::InvalidPresentationSchema => 'Create presentation schema',
            self::ContextSchemaMismatch => 'Validate view context against schema',
            self::InvalidRenderLimits => 'Create render limits',
            self::InvalidRenderRequest => 'Create render request',
            self::InvalidRenderResult => 'Create render result',
            self::InvalidRenderFailure => 'Create render failure evidence',
            self::ContractBoundExceeded => 'Validate view contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Identities must preserve their exact bounded canonical grammar',
            self::InvalidPresentationValue => 'Presentation values must remain closed, typed, bounded, and control-safe',
            self::InvalidPresentationSchema => 'Presentation schemas must remain closed, unique, canonical, and bounded',
            self::ContextSchemaMismatch => 'Required, optional, declared, kind, and bound relations must match recursively',
            self::InvalidRenderLimits => 'Output-byte and render-step limits must remain finite and in range',
            self::InvalidRenderRequest => 'A request must carry one exact validated context and inert identity set',
            self::InvalidRenderResult => 'Completed output, metadata, counts, identities, and state must agree',
            self::InvalidRenderFailure => 'Failure evidence must carry only the exact closed failed-state shape',
            self::ContractBoundExceeded => 'Every value, schema, output, depth, node, and collection bound is fail-closed',
        };
    }
}
