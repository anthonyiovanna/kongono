<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for the cache runtime. */
#[Api(ApiStatus::Internal)]
enum CacheRuntimeDiagnostic: string
{
    case InvalidBinding = 'KGN-CACHE-RUNTIME-2031';
    case InvalidOperation = 'KGN-CACHE-RUNTIME-2032';
    case InvalidAdapterEvidence = 'KGN-CACHE-RUNTIME-2033';
    case AdapterFailed = 'KGN-CACHE-RUNTIME-2034';
    case ReentrantOperation = 'KGN-CACHE-RUNTIME-2035';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('operation', 'Operation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('byte_count', 'Byte count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('active', 'Operation active', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe, false),
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
            self::InvalidBinding => 'Cache runtime binding is invalid',
            self::InvalidOperation => 'Cache runtime operation is invalid',
            self::InvalidAdapterEvidence => 'Cache adapter evidence is invalid',
            self::AdapterFailed => 'Cache adapter execution failed',
            self::ReentrantOperation => 'Cache runtime operation is already active',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Supply one exact-current profile artifact and a matching bounded Version 1 adapter',
            self::InvalidOperation => 'Supply one structurally valid bounded request and caller-owned instant',
            self::InvalidAdapterEvidence => 'Correct the adapter to return complete evidence matching the exact request and selected profile',
            self::AdapterFailed => 'Inspect the retained prior cause and restore the directly supplied adapter before retrying explicitly',
            self::ReentrantOperation => 'Wait for the active operation to retire or use a separate runtime instance',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Bind exact-current cache runtime',
            self::InvalidOperation => 'Validate cache runtime operation',
            self::InvalidAdapterEvidence => 'Validate cache adapter result',
            self::AdapterFailed => 'Execute cache adapter operation',
            self::ReentrantOperation => 'Enter cache runtime operation',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Binding requires one exact-current selected profile and one matching Version 1 bounded adapter',
            self::InvalidOperation => 'Operation inputs, value bounds, and time arithmetic must be valid before adapter invocation',
            self::InvalidAdapterEvidence => 'Adapter evidence must exactly match the operation, key, selected profile, and candidate entry',
            self::AdapterFailed => 'Adapter failures remain terminal, causal, nondisclosing, and non-retried',
            self::ReentrantOperation => 'One runtime instance may have at most one active adapter invocation',
        };
    }
}
