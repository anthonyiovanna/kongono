<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Diagnostic\GraphContractDiagnostic;
use Throwable;

/** Reports one bounded failure to construct an exact Graph contract value. */
#[Api(ApiStatus::Experimental)]
final class InvalidGraphContractException extends GraphContractException
{
    private const array IDENTITY_VIOLATIONS = ['digest_grammar'];
    private const array FIELD_NAME_VIOLATIONS = ['field_name_grammar'];
    private const array SCHEMA_VIOLATIONS = [
        'duplicate_field_name',
        'duplicate_node_type',
        'fields_not_list',
        'invalid_field_entry',
        'invalid_node_type_entry',
        'node_types_not_list',
        'non_canonical_field_order',
        'non_canonical_node_type_order',
        'required_endpoints_empty',
    ];
    private const array PROVENANCE_VIOLATIONS = ['source_digest_grammar'];
    private const array LIMIT_VIOLATIONS = ['field_count', 'source_type_count', 'target_type_count'];

    public static function identity(string $violation, ?Throwable $previous = null): self
    {
        return new self(
            GraphContractDiagnostic::InvalidIdentity,
            ['violation' => self::safeViolation($violation, self::IDENTITY_VIOLATIONS)],
            $previous,
        );
    }

    public static function fieldName(string $violation, ?Throwable $previous = null): self
    {
        return new self(
            GraphContractDiagnostic::InvalidFieldName,
            ['violation' => self::safeViolation($violation, self::FIELD_NAME_VIOLATIONS)],
            $previous,
        );
    }

    public static function schema(
        string $violation,
        ?int $entryCount = null,
        ?int $entryPosition = null,
        ?Throwable $previous = null,
    ): self {
        $context = ['violation' => self::safeViolation($violation, self::SCHEMA_VIOLATIONS)];

        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }

        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return new self(GraphContractDiagnostic::InvalidSchema, $context, $previous);
    }

    public static function provenance(string $violation, ?Throwable $previous = null): self
    {
        return new self(
            GraphContractDiagnostic::InvalidProvenance,
            ['violation' => self::safeViolation($violation, self::PROVENANCE_VIOLATIONS)],
            $previous,
        );
    }

    public static function limit(string $violation, int $entryCount, ?Throwable $previous = null): self
    {
        return new self(
            GraphContractDiagnostic::ContractLimitExceeded,
            [
                'violation' => self::safeViolation($violation, self::LIMIT_VIOLATIONS),
                'entry_count' => max(0, $entryCount),
            ],
            $previous,
        );
    }

    /** @param list<string> $allowed */
    private static function safeViolation(string $violation, array $allowed): string
    {
        return in_array($violation, $allowed, true) ? $violation : 'unsupported_violation';
    }
}
