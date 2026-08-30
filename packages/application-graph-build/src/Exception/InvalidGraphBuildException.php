<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Diagnostic\GraphBuildDiagnostic;
use Throwable;

/** Reports one bounded all-or-nothing IR admission or construction failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidGraphBuildException extends GraphBuildException
{
    private const array INPUT_VIOLATIONS = [
        'field_value_grammar',
        'fields_not_list',
        'invalid_field_entry',
        'invalid_node_record',
        'invalid_relation_record',
        'node_records_not_list',
        'non_canonical_field_order',
        'relation_records_not_list',
        'unsupported_contract_version',
    ];
    private const array FIELD_VIOLATIONS = ['duplicate_field_name', 'missing_required_field', 'unknown_field'];
    private const array DUPLICATE_VIOLATIONS = ['duplicate_node_identity', 'duplicate_relation_identity', 'duplicate_relation_tuple'];
    private const array ENDPOINT_VIOLATIONS = ['missing_source', 'missing_target', 'source_type', 'target_type'];
    private const array LIMIT_VIOLATIONS = ['field_count', 'node_count', 'relation_count'];
    private const array RECORD_KINDS = ['field', 'node', 'relation'];

    public static function input(
        string $violation,
        string $recordKind,
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?Throwable $previous = null,
    ): self {
        return new self(
            GraphBuildDiagnostic::InvalidInput,
            self::context($violation, self::INPUT_VIOLATIONS, $recordKind, $entryCount, $entryPosition),
            $previous,
        );
    }

    public static function fieldSet(
        string $violation,
        string $recordKind,
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?Throwable $previous = null,
    ): self {
        return new self(
            GraphBuildDiagnostic::InvalidFieldSet,
            self::context($violation, self::FIELD_VIOLATIONS, $recordKind, $entryCount, $entryPosition),
            $previous,
        );
    }

    public static function duplicate(string $violation, string $recordKind, ?Throwable $previous = null): self
    {
        return new self(
            GraphBuildDiagnostic::Duplicate,
            self::context($violation, self::DUPLICATE_VIOLATIONS, $recordKind),
            $previous,
        );
    }

    public static function endpoint(string $violation, ?Throwable $previous = null): self
    {
        return new self(
            GraphBuildDiagnostic::InvalidEndpoint,
            self::context($violation, self::ENDPOINT_VIOLATIONS, 'relation'),
            $previous,
        );
    }

    public static function limit(
        string $violation,
        string $recordKind,
        int $entryCount,
        ?Throwable $previous = null,
    ): self {
        return new self(
            GraphBuildDiagnostic::BoundExceeded,
            self::context($violation, self::LIMIT_VIOLATIONS, $recordKind, $entryCount),
            $previous,
        );
    }

    /**
     * @param list<string> $allowed
     * @return array<string, string|int|bool>
     */
    private static function context(
        string $violation,
        array $allowed,
        string $recordKind,
        ?int $entryCount = null,
        ?int $entryPosition = null,
    ): array {
        $context = [
            'violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation',
            'record_kind' => in_array($recordKind, self::RECORD_KINDS, true) ? $recordKind : 'field',
        ];

        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return $context;
    }
}
