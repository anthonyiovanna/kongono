<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for the storage runtime. */
#[Api(ApiStatus::Internal)]
enum StorageRuntimeDiagnostic: string
{
    case InvalidBinding = 'KGN-STORAGE-RUNTIME-2131';
    case InvalidOperation = 'KGN-STORAGE-RUNTIME-2132';
    case InvalidAdapterEvidence = 'KGN-STORAGE-RUNTIME-2133';
    case AdapterFailed = 'KGN-STORAGE-RUNTIME-2134';
    case ReentrantOperation = 'KGN-STORAGE-RUNTIME-2135';
    case CleanupFailed = 'KGN-STORAGE-RUNTIME-2136';
    case OperationAndCleanupFailed = 'KGN-STORAGE-RUNTIME-2137';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('operation', 'Operation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('byte_count', 'Byte count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('active', 'Operation active', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('cleanup_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('primary_present', 'Primary failure present', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe, false),
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
            self::InvalidBinding => 'Storage runtime binding is invalid',
            self::InvalidOperation => 'Storage runtime operation is invalid',
            self::InvalidAdapterEvidence => 'Storage adapter evidence is invalid',
            self::AdapterFailed => 'Storage adapter execution failed',
            self::ReentrantOperation => 'Storage runtime operation is already active',
            self::CleanupFailed => 'Storage runtime cleanup failed',
            self::OperationAndCleanupFailed => 'Storage runtime operation and cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Supply one exact-current profile artifact and matching Version 1 neutral adapter',
            self::InvalidOperation => 'Supply one valid bounded request and open source where required',
            self::InvalidAdapterEvidence => 'Correct the adapter to return evidence matching the request and selected profile',
            self::AdapterFailed => 'Inspect the retained cause and restore the supplied adapter before an explicit retry',
            self::ReentrantOperation => 'Close or exhaust the active read or use a separate runtime instance',
            self::CleanupFailed => 'Inspect the retained cleanup causes and restore deterministic lifecycle cleanup',
            self::OperationAndCleanupFailed => 'Inspect the retained primary and cleanup causes separately',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Bind exact-current storage runtime',
            self::InvalidOperation => 'Validate storage runtime operation',
            self::InvalidAdapterEvidence => 'Validate storage adapter evidence',
            self::AdapterFailed => 'Execute storage adapter operation',
            self::ReentrantOperation => 'Enter storage runtime operation',
            self::CleanupFailed => 'Retire storage runtime resources',
            self::OperationAndCleanupFailed => 'Preserve storage operation and cleanup truth',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Binding requires one exact-current profile and a matching neutral adapter descriptor',
            self::InvalidOperation => 'Operation inputs must match the selected profile before any adapter effect',
            self::InvalidAdapterEvidence => 'Adapter evidence must exactly match the request and selected profile',
            self::AdapterFailed => 'Adapter failure remains causal terminal nondisclosing evidence and is never retried',
            self::ReentrantOperation => 'One runtime instance may have at most one active operation',
            self::CleanupFailed => 'Every operation-owned resource and scope must retire without swallowed failure',
            self::OperationAndCleanupFailed => 'Primary and cleanup failures remain separately retained',
        };
    }
}
