<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;

/** Centralizes exact local validation without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public const int MAX_VALUE_BYTES = 1_048_576;
    public const int MAX_INSTANT_MICROSECONDS = 253_402_300_799_999_999;
    public const int MAX_LIFETIME_MICROSECONDS = 31_536_000_000_000;

    public static function sameContractVersion(ContractVersion $left, ContractVersion $right): bool
    {
        return match ($left) {
            ContractVersion::Version1 => match ($right) {
                ContractVersion::Version1 => true,
            },
        };
    }

    public static function cacheNamespace(string $value): string
    {
        return self::grammaticalIdentity($value, 64, 'cache_namespace_bytes', 'cache_namespace_grammar');
    }

    public static function logicalCacheKey(string $value): string
    {
        $length = strlen($value);
        if ($length > 256) {
            throw InvalidCacheContractException::contractBoundExceeded('logical_cache_key_bytes', $length, 256);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidCacheContractException::invalidIdentity('logical_cache_key_grammar');
        }

        return $value;
    }

    public static function codecName(string $value): string
    {
        return self::grammaticalIdentity($value, 96, 'codec_name_bytes', 'codec_name_grammar');
    }

    public static function codecVersion(int $version): int
    {
        if ($version < 1 || $version > 65_535) {
            throw InvalidCacheContractException::invalidIdentity('codec_version_range');
        }

        return $version;
    }

    public static function cacheKeyIdentity(string $digest): string
    {
        return self::digest($digest, 'cache_key_identity_grammar');
    }

    public static function cacheContentIdentity(string $digest): string
    {
        return self::digest($digest, 'cache_content_identity_grammar');
    }

    public static function cacheValueBytes(string $bytes): void
    {
        $length = strlen($bytes);
        if ($length > self::MAX_VALUE_BYTES) {
            throw InvalidCacheContractException::contractBoundExceeded('cache_value_bytes', $length, self::MAX_VALUE_BYTES);
        }
    }

    public static function cacheInstant(int $microseconds): int
    {
        if ($microseconds < 0 || $microseconds > self::MAX_INSTANT_MICROSECONDS) {
            throw InvalidCacheContractException::invalidFreshnessEvidence('instant_range');
        }

        return $microseconds;
    }

    public static function cacheLifetime(int $microseconds): int
    {
        if ($microseconds < 1 || $microseconds > self::MAX_LIFETIME_MICROSECONDS) {
            throw InvalidCacheContractException::invalidFreshnessEvidence('lifetime_range');
        }

        return $microseconds;
    }

    public static function freshnessWindow(CacheInstant $storedAt, CacheInstant $expiresAt): void
    {
        if ($expiresAt->compareTo($storedAt) <= 0) {
            throw InvalidCacheContractException::invalidFreshnessEvidence('expiry_not_after_stored');
        }
        if ($expiresAt->microseconds() - $storedAt->microseconds() > self::MAX_LIFETIME_MICROSECONDS) {
            throw InvalidCacheContractException::invalidFreshnessEvidence('lifetime_exceeded');
        }
    }

    public static function nowBeforeStoredAt(): InvalidCacheContractException
    {
        return InvalidCacheContractException::invalidFreshnessEvidence('now_before_stored_at');
    }

    public static function entryVersion(string $token): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
            throw InvalidCacheContractException::invalidEntryVersion('entry_version_grammar');
        }

        return $token;
    }

    public static function entryEvidence(ContractVersion $version, CacheValueBytes $bytes, CacheContentIdentity $contentIdentity): void
    {
        if (!self::sameContractVersion($version, ContractVersion::Version1)) {
            throw InvalidCacheContractException::invalidEntryEvidence('contract_version_relation');
        }
        if (!$bytes->contentIdentity()->equals($contentIdentity)) {
            throw InvalidCacheContractException::invalidEntryEvidence('content_identity_mismatch');
        }
    }

    private static function grammaticalIdentity(string $value, int $limit, string $boundViolation, string $grammarViolation): string
    {
        $length = strlen($value);
        if ($length > $limit) {
            throw InvalidCacheContractException::contractBoundExceeded($boundViolation, $length, $limit);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidCacheContractException::invalidIdentity($grammarViolation);
        }

        return $value;
    }

    private static function digest(string $value, string $violation): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidCacheContractException::invalidIdentity($violation);
        }

        return $value;
    }
}
