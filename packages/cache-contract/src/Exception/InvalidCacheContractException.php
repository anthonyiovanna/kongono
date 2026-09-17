<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Diagnostic\CacheContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct cache-contract evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidCacheContractException extends CacheContractException
{
    private const array IDENTITY_VIOLATIONS = ['cache_namespace_grammar', 'logical_cache_key_grammar', 'cache_key_identity_grammar', 'cache_content_identity_grammar', 'codec_name_grammar', 'codec_version_range'];
    private const array VALUE_VIOLATIONS = ['value_bytes_invalid'];
    private const array FRESHNESS_VIOLATIONS = ['instant_range', 'lifetime_range', 'expiry_not_after_stored', 'lifetime_exceeded', 'now_before_stored_at'];
    private const array VERSION_VIOLATIONS = ['entry_version_grammar'];
    private const array ENTRY_VIOLATIONS = ['contract_version_relation', 'content_identity_mismatch'];
    private const array BOUND_VIOLATIONS = ['cache_namespace_bytes', 'logical_cache_key_bytes', 'codec_name_bytes', 'cache_value_bytes'];
    private const array OPERATIONS = ['lookup', 'store', 'replace', 'remove'];
    private const array OUTCOMES = ['fresh_hit', 'miss', 'expired', 'stored', 'removed', 'not_found', 'conflict', 'rejected', 'unavailable'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(CacheContractDiagnostic::InvalidIdentity, self::context($violation, self::IDENTITY_VIOLATIONS), $previous);
    }

    public static function invalidValueBytes(string $violation, ?int $byteCount = null, ?int $limit = null, ?Throwable $previous = null): self
    {
        return new self(CacheContractDiagnostic::InvalidValueBytes, self::context($violation, self::VALUE_VIOLATIONS, $byteCount, $limit), $previous);
    }

    public static function invalidFreshnessEvidence(string $violation, ?Throwable $previous = null): self
    {
        return new self(CacheContractDiagnostic::InvalidFreshnessEvidence, self::context($violation, self::FRESHNESS_VIOLATIONS), $previous);
    }

    public static function invalidEntryVersion(string $violation, ?Throwable $previous = null): self
    {
        return new self(CacheContractDiagnostic::InvalidEntryVersion, self::context($violation, self::VERSION_VIOLATIONS), $previous);
    }

    public static function invalidEntryEvidence(string $violation, ?string $operation = null, ?string $outcome = null, ?Throwable $previous = null): self
    {
        return new self(CacheContractDiagnostic::InvalidEntryEvidence, self::context($violation, self::ENTRY_VIOLATIONS, operation: $operation, outcome: $outcome), $previous);
    }

    public static function contractBoundExceeded(string $violation, int $byteCount, int $limit, ?Throwable $previous = null): self
    {
        return new self(CacheContractDiagnostic::ContractBoundExceeded, self::context($violation, self::BOUND_VIOLATIONS, max(0, $byteCount), max(0, $limit)), $previous);
    }

    /**
     * @param list<string> $allowed
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, array $allowed, ?int $byteCount = null, ?int $limit = null, ?string $operation = null, ?string $outcome = null): array
    {
        $context = ['violation' => self::safe($violation, $allowed, 'unsupported_violation')];
        if (null !== $byteCount) {
            $context['byte_count'] = max(0, $byteCount);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }
        if (null !== $operation) {
            $context['operation'] = self::safe($operation, self::OPERATIONS, 'unsupported_operation');
        }
        if (null !== $outcome) {
            $context['outcome'] = self::safe($outcome, self::OUTCOMES, 'unsupported_outcome');
        }

        return $context;
    }

    /** @param list<string> $allowed */
    private static function safe(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
