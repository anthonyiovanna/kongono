<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for storage-adapter contracts. */
#[Api(ApiStatus::Internal)]
enum StorageAdapterContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-STORAGE-ADAPTER-CONTRACT-2111';
    case InvalidDescriptor = 'KGN-STORAGE-ADAPTER-CONTRACT-2112';
    case InvalidSet = 'KGN-STORAGE-ADAPTER-CONTRACT-2113';
    case InvalidRead = 'KGN-STORAGE-ADAPTER-CONTRACT-2114';
    case ExecutionFailure = 'KGN-STORAGE-ADAPTER-CONTRACT-2115';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema('Validate storage adapter protocol', 'Adapter evidence remains bounded and relationally complete', [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('count', 'Count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'Storage adapter identity is invalid',
            self::InvalidDescriptor => 'Storage adapter descriptor is invalid',
            self::InvalidSet => 'Storage adapter claim set is invalid',
            self::InvalidRead => 'Storage adapter read relation is invalid',
            self::ExecutionFailure => 'Storage adapter execution failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply the documented bounded adapter identity and revision',
            self::InvalidDescriptor => 'Bind one exact profile present in the validated artifact',
            self::InvalidSet => 'Supply a unique list of closed claims',
            self::InvalidRead => 'Relate completed evidence to exactly one matching open source',
            self::ExecutionFailure => 'Treat execution as failed without inventing an outcome',
        };
    }
}
