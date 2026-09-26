<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Diagnostic\StorageContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct neutral storage evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidStorageContractException extends StorageContractException
{
    private const array IDENTITY_VIOLATIONS = ['storage_namespace_grammar', 'logical_object_identity_grammar', 'operation_identity_grammar'];
    private const array CAPABILITY_VIOLATIONS = ['capabilities_not_list', 'invalid_capability_entry'];
    private const array CONTENT_VIOLATIONS = ['byte_count_range', 'chunk_empty', 'media_type_grammar', 'digest_grammar', 'metadata_name_grammar', 'metadata_value_utf8', 'metadata_value_control', 'object_version_grammar', 'continuation_token_grammar'];
    private const array CONDITION_VIOLATIONS = ['create_condition_relation', 'replace_condition_relation', 'delete_condition_relation'];
    private const array REQUEST_VIOLATIONS = ['unsupported_write_operation', 'page_size_range'];
    private const array RESULT_VIOLATIONS = ['metadata_entries_not_list', 'invalid_metadata_entry', 'duplicate_metadata_entry', 'list_entries_not_list', 'invalid_list_entry', 'duplicate_or_unordered_list_entry', 'page_continuation_relation', 'read_result_evidence_relation', 'mutation_result_evidence_relation', 'list_result_page_relation'];
    private const array STREAM_VIOLATIONS = ['maximum_bytes_range', 'read_after_close', 'read_null_before_exhaustion', 'write_after_close', 'invalid_close_or_abort'];
    private const array BOUND_VIOLATIONS = ['namespace_bytes', 'logical_identity_bytes', 'operation_identity_bytes', 'version_bytes', 'continuation_bytes', 'media_type_bytes', 'metadata_entries', 'metadata_name_bytes', 'metadata_value_bytes', 'chunk_bytes', 'object_bytes', 'list_page_size'];
    private const array OPERATIONS = ['read', 'create', 'replace', 'delete', 'list'];
    private const array OUTCOMES = ['completed', 'absent', 'already_exists', 'conflict', 'rejected', 'unsupported', 'unavailable', 'uncertain'];
    private const array CAPABILITIES = ['read', 'create', 'replace', 'delete', 'list', 'metadata', 'content_digest', 'conditional_mutation'];
    private const array DIGEST_ALGORITHMS = ['sha256', 'sha384', 'sha512'];

    public static function invalidIdentity(string $violation, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidIdentity, self::context($violation, self::IDENTITY_VIOLATIONS), $previous);
    }

    public static function invalidCapability(string $violation, ?string $capability = null, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidCapability, self::context($violation, self::CAPABILITY_VIOLATIONS, capability: $capability), $previous);
    }

    public static function invalidContentEvidence(string $violation, ?string $digestAlgorithm = null, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidContentEvidence, self::context($violation, self::CONTENT_VIOLATIONS, digestAlgorithm: $digestAlgorithm), $previous);
    }

    public static function invalidMutationCondition(string $violation, ?string $operation = null, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidMutationCondition, self::context($violation, self::CONDITION_VIOLATIONS, operation: $operation), $previous);
    }

    public static function invalidRequest(string $violation, ?string $operation = null, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidRequest, self::context($violation, self::REQUEST_VIOLATIONS, operation: $operation), $previous);
    }

    public static function invalidResult(string $violation, ?string $outcome = null, ?int $entryCount = null, ?int $entryPosition = null, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidResult, self::context($violation, self::RESULT_VIOLATIONS, outcome: $outcome, entryCount: $entryCount, entryPosition: $entryPosition), $previous);
    }

    public static function invalidStreamContract(string $violation, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::InvalidStreamContract, self::context($violation, self::STREAM_VIOLATIONS), $previous);
    }

    public static function contractBoundExceeded(string $violation, int $limit, ?int $entryCount = null, ?Throwable $previous = null): self
    {
        return new self(StorageContractDiagnostic::ContractBoundExceeded, self::context($violation, self::BOUND_VIOLATIONS, entryCount: $entryCount, limit: $limit), $previous);
    }

    /**
     * @param list<string> $allowedViolations
     * @return array<string, string|int|bool>
     */
    private static function context(
        string $violation,
        array $allowedViolations,
        ?string $operation = null,
        ?string $outcome = null,
        ?string $capability = null,
        ?string $digestAlgorithm = null,
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?int $limit = null,
    ): array {
        $context = ['violation' => self::safe($violation, $allowedViolations, 'unsupported_violation')];
        if (null !== $operation) {
            $context['operation'] = self::safe($operation, self::OPERATIONS, 'unsupported_operation');
        }
        if (null !== $outcome) {
            $context['outcome'] = self::safe($outcome, self::OUTCOMES, 'unsupported_outcome');
        }
        if (null !== $capability) {
            $context['capability'] = self::safe($capability, self::CAPABILITIES, 'unsupported_capability');
        }
        if (null !== $digestAlgorithm) {
            $context['digest_algorithm'] = self::safe($digestAlgorithm, self::DIGEST_ALGORITHMS, 'unsupported_digest_algorithm');
        }
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
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
