<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Diagnostic\CacheAdapterContractDiagnostic;
use Kongono\CacheAdapterContract\Exception\InvalidCacheAdapterContractException;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupStatus;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Value\CacheValueBytes;

/** Centralizes exact adapter-protocol validation without a generic public validator. */
#[Api(ApiStatus::Internal)]
final class AdapterContractGuard
{
    public static function adapterName(string $name): string
    {
        $length = strlen($name);
        if ($length < 1 || $length > 64 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $name)) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidIdentity, 'adapter_identity_grammar');
        }

        return $name;
    }

    public static function positiveVersion(int $version): int
    {
        if ($version < 1 || $version > 65_535) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidIdentity, 'adapter_version_range');
        }

        return $version;
    }

    public static function limits(int $entries, int $aggregateBytes, int $valueBytes): void
    {
        if ($entries < 1 || $entries > 4_096) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidLimits, 'entry_count_range', count: $entries, limit: 4_096);
        }
        if ($aggregateBytes < 1 || $aggregateBytes > 67_108_864) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidLimits, 'aggregate_value_bytes_range', count: $aggregateBytes, limit: 67_108_864);
        }
        if ($valueBytes < 1 || $valueBytes > 1_048_576) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidLimits, 'single_value_bytes_range', count: $valueBytes, limit: 1_048_576);
        }
        if ($valueBytes > $aggregateBytes) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidLimits, 'single_exceeds_aggregate');
        }
    }

    public static function entry(ContractVersion $version, CacheValueBytes $bytes, CacheContentIdentity $content): void
    {
        if (!$bytes->contentIdentity()->equals($content)) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidEntry, 'content_identity_mismatch');
        }
    }

    public static function lookupResult(CacheAdapterLookupStatus $status, CacheKeyIdentity $key, ?CacheEntryEvidence $evidence): void
    {
        if (CacheAdapterLookupStatus::Found === $status) {
            if (null === $evidence) {
                throw self::invalid(CacheAdapterContractDiagnostic::InvalidResult, 'found_without_evidence', status: $status->value);
            }
            if (!$key->equals($evidence->keyIdentity())) {
                throw self::invalid(CacheAdapterContractDiagnostic::InvalidResult, 'foreign_key_evidence', status: $status->value);
            }

            return;
        }
        if (null !== $evidence) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidResult, 'non_found_with_evidence', status: $status->value);
        }
    }

    public static function mutationResult(CacheMutationOutcome $outcome, CacheKeyIdentity $key, ?CacheEntryEvidence $evidence): void
    {
        if (CacheMutationOutcome::Stored === $outcome) {
            if (null === $evidence) {
                throw self::invalid(CacheAdapterContractDiagnostic::InvalidResult, 'stored_without_evidence', outcome: $outcome->value);
            }
            if (!$key->equals($evidence->keyIdentity())) {
                throw self::invalid(CacheAdapterContractDiagnostic::InvalidResult, 'foreign_key_evidence', outcome: $outcome->value);
            }

            return;
        }
        if (null !== $evidence) {
            throw self::invalid(CacheAdapterContractDiagnostic::InvalidResult, 'non_stored_with_evidence', outcome: $outcome->value);
        }
    }

    private static function invalid(
        CacheAdapterContractDiagnostic $diagnostic,
        string $violation,
        ?string $status = null,
        ?string $outcome = null,
        ?int $count = null,
        ?int $limit = null,
    ): InvalidCacheAdapterContractException {
        $context = ['violation' => $violation];
        if (null !== $status) {
            $context['status'] = $status;
        }
        if (null !== $outcome) {
            $context['outcome'] = $outcome;
        }
        if (null !== $count) {
            $context['count'] = max(0, $count);
        }
        if (null !== $limit) {
            $context['limit'] = $limit;
        }

        return InvalidCacheAdapterContractException::forDiagnostic($diagnostic, $context);
    }
}
