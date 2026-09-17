<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheRuntime\Diagnostic\CacheRuntimeDiagnostic;
use Throwable;

/** Reports invalid binding, request relation, or adapter evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidCacheRuntimeException extends CacheRuntimeException
{
    private const array BINDING = ['artifact_invalid', 'artifact_stale', 'profile_missing', 'protocol_mismatch', 'adapter_identity_mismatch', 'adapter_limits', 'binding_failure'];
    private const array OPERATION = ['value_limit', 'instant_overflow', 'request_relation', 'result_relation'];
    private const array EVIDENCE = ['key_mismatch', 'outcome_not_allowed', 'entry_presence', 'contract_mismatch', 'codec_mismatch', 'value_limit', 'content_mismatch', 'freshness_mismatch', 'candidate_mismatch', 'time_relation'];
    private const array OPERATIONS = ['lookup', 'store', 'replace', 'remove'];
    private const array OUTCOMES = ['found', 'miss', 'stored', 'removed', 'not_found', 'conflict', 'rejected', 'unavailable'];

    public static function invalidBinding(string $violation, ?Throwable $previous = null, ?int $byteCount = null, ?int $limit = null): self
    {
        return new self(CacheRuntimeDiagnostic::InvalidBinding, self::context($violation, self::BINDING, byteCount: $byteCount, limit: $limit), $previous);
    }

    public static function invalidOperation(string $violation, ?string $operation = null, ?int $byteCount = null, ?int $limit = null, ?Throwable $previous = null): self
    {
        return new self(CacheRuntimeDiagnostic::InvalidOperation, self::context($violation, self::OPERATION, $operation, byteCount: $byteCount, limit: $limit), $previous);
    }

    public static function invalidAdapterEvidence(string $violation, string $operation, ?string $outcome = null, ?Throwable $previous = null, ?int $byteCount = null, ?int $limit = null): self
    {
        return new self(CacheRuntimeDiagnostic::InvalidAdapterEvidence, self::context($violation, self::EVIDENCE, $operation, $outcome, $byteCount, $limit), $previous);
    }

    /**
     * @param list<string> $allowed
     * @return array<string, string|int|bool>
     */
    private static function context(string $violation, array $allowed, ?string $operation = null, ?string $outcome = null, ?int $byteCount = null, ?int $limit = null): array
    {
        $context = ['violation' => self::safe($violation, $allowed, 'unsupported_violation')];
        if (null !== $operation) {
            $context['operation'] = self::safe($operation, self::OPERATIONS, 'unsupported_operation');
        }
        if (null !== $outcome) {
            $context['outcome'] = self::safe($outcome, self::OUTCOMES, 'unsupported_outcome');
        }
        if (null !== $byteCount) {
            $context['byte_count'] = max(0, $byteCount);
        }
        if (null !== $limit) {
            $context['limit'] = max(0, $limit);
        }

        return $context;
    }

    /** @param list<string> $allowed */
    private static function safe(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
