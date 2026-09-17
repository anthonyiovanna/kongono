<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for cache contracts. */
#[Api(ApiStatus::Internal)]
enum CacheContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-CACHE-CONTRACT-2001';
    case InvalidValueBytes = 'KGN-CACHE-CONTRACT-2002';
    case InvalidFreshnessEvidence = 'KGN-CACHE-CONTRACT-2003';
    case InvalidEntryVersion = 'KGN-CACHE-CONTRACT-2004';
    case InvalidEntryEvidence = 'KGN-CACHE-CONTRACT-2005';
    case ContractBoundExceeded = 'KGN-CACHE-CONTRACT-2006';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('byte_count', 'Byte count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('operation', 'Operation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidIdentity => 'Cache contract identity is invalid',
            self::InvalidValueBytes => 'Cache value bytes are invalid',
            self::InvalidFreshnessEvidence => 'Cache freshness evidence is invalid',
            self::InvalidEntryVersion => 'Cache entry version is invalid',
            self::InvalidEntryEvidence => 'Cache entry evidence is invalid',
            self::ContractBoundExceeded => 'Cache contract bound is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply the documented bounded canonical cache identity',
            self::InvalidValueBytes => 'Supply opaque value bytes within the documented byte bound',
            self::InvalidFreshnessEvidence => 'Supply ordered caller-owned instants and one finite supported lifetime',
            self::InvalidEntryVersion => 'Supply one lowercase 64-character hexadecimal entry-version token',
            self::InvalidEntryEvidence => 'Supply Version 1 detached evidence whose content identity matches its bytes',
            self::ContractBoundExceeded => 'Reduce the supplied identity or byte string to the documented bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create cache contract identity',
            self::InvalidValueBytes => 'Create cache value evidence',
            self::InvalidFreshnessEvidence => 'Create cache freshness evidence',
            self::InvalidEntryVersion => 'Create cache entry-version evidence',
            self::InvalidEntryEvidence => 'Create detached cache entry evidence',
            self::ContractBoundExceeded => 'Validate cache contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Namespace, key, codec, key identity, and content identity must preserve exact canonical domains',
            self::InvalidValueBytes => 'Cache values remain opaque bounded bytes and empty bytes remain valid',
            self::InvalidFreshnessEvidence => 'Time is caller supplied, ordered, finite, and evaluated at the exact expiry boundary',
            self::InvalidEntryVersion => 'Entry versions remain lowercase fixed-width opaque mutation evidence',
            self::InvalidEntryEvidence => 'Detached entry evidence is Version 1 and content-consistent',
            self::ContractBoundExceeded => 'Every caller-supplied identity and byte-string bound is fail-closed',
        };
    }
}
