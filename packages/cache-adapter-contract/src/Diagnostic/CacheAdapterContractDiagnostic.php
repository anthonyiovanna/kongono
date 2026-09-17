<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for adapter contracts. */
#[Api(ApiStatus::Internal)]
enum CacheAdapterContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-CACHE-ADAPTER-CONTRACT-2011';
    case InvalidLimits = 'KGN-CACHE-ADAPTER-CONTRACT-2012';
    case InvalidEntry = 'KGN-CACHE-ADAPTER-CONTRACT-2013';
    case InvalidResult = 'KGN-CACHE-ADAPTER-CONTRACT-2014';
    case ExecutionFailure = 'KGN-CACHE-ADAPTER-CONTRACT-2015';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('status', 'Status', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('count', 'Count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'Cache adapter identity is invalid',
            self::InvalidLimits => 'Cache adapter limits are invalid',
            self::InvalidEntry => 'Cache adapter entry is invalid',
            self::InvalidResult => 'Cache adapter result is invalid',
            self::ExecutionFailure => 'Cache adapter execution failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply the documented bounded adapter name and positive version',
            self::InvalidLimits => 'Supply finite related entry and value-byte limits',
            self::InvalidEntry => 'Supply complete Version 1 entry evidence with matching content identity',
            self::InvalidResult => 'Supply one possible closed status and evidence relation',
            self::ExecutionFailure => 'Treat the adapter operation as failed without inventing success',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create cache adapter identity',
            self::InvalidLimits => 'Create cache adapter limits',
            self::InvalidEntry => 'Create cache adapter entry',
            self::InvalidResult => 'Create cache adapter result',
            self::ExecutionFailure => 'Execute cache adapter operation',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Adapter identity remains grammatical, bounded, and explicitly versioned',
            self::InvalidLimits => 'All declared limits are positive, finite, bounded, and mutually consistent',
            self::InvalidEntry => 'Adapter entries preserve the neutral Version 1 key, codec, content, bytes, and freshness relation',
            self::InvalidResult => 'Only found and stored states carry matching complete entry evidence',
            self::ExecutionFailure => 'Execution failure never becomes a successful adapter outcome',
        };
    }
}
