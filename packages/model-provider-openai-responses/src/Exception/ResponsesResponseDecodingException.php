<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Throwable;

/** Reports a bounded response mapping failure without exposing response values. */
#[Api(ApiStatus::Experimental)]
final class ResponsesResponseDecodingException extends OpenAiResponsesException
{
    private const array SYNTAX_VIOLATIONS = ['utf8', 'json_syntax', 'json_depth', 'top_level_object', 'duplicate_member'];
    private const array STRUCTURE_VIOLATIONS = ['missing_field', 'field_type', 'object_value', 'response_id', 'model_claim', 'model_mismatch', 'terminal_status', 'status_relation', 'response_relation', 'output_shape', 'content_shape', 'text_value', 'usage_type', 'usage_range', 'usage_relation', 'incomplete_details', 'function_id', 'call_id', 'function_name', 'arguments_value', 'schema_value'];
    private const array LIMIT_VIOLATIONS = ['response_body_bytes', 'output_count', 'output_text_bytes', 'action_arguments_bytes'];
    private const array MAPPING_VIOLATIONS = ['object_members', 'output_item', 'content_item', 'response_status', 'incomplete_reason', 'result_mode'];
    private const array FIELDS = ['body', 'object', 'id', 'model', 'status', 'output', 'output_item', 'content', 'content_item', 'text', 'usage', 'incomplete_details', 'arguments', 'call_id', 'name', 'schema'];

    public static function invalidSyntax(string $violation, ?Throwable $previous = null, OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1): self
    {
        return new self(OpenAiResponsesDiagnostic::ResponseSyntaxInvalid, [
            'violation' => self::safeToken($violation, self::SYNTAX_VIOLATIONS, 'unsupported_violation'),
            'mapping_version' => self::mappingVersion($mappingVersion),
        ], $previous);
    }

    public static function invalidStructure(string $violation, string $field, ?string $status = null, ?Throwable $previous = null, OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1): self
    {
        $context = [
            'violation' => self::safeToken($violation, self::STRUCTURE_VIOLATIONS, 'unsupported_violation'),
            'field' => self::safeToken($field, self::FIELDS, 'unsupported_field'),
            'mapping_version' => self::mappingVersion($mappingVersion),
        ];
        if (null !== $status) {
            $context['status'] = self::safeToken($status, ['completed', 'incomplete'], 'unsupported_status');
        }

        return new self(OpenAiResponsesDiagnostic::ResponseStructureInvalid, $context, $previous);
    }

    public static function limitExceeded(string $violation, int $limit, ?int $entryCount = null, ?Throwable $previous = null, OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1): self
    {
        $context = [
            'violation' => self::safeToken($violation, self::LIMIT_VIOLATIONS, 'unsupported_violation'),
            'limit' => max(0, $limit),
            'mapping_version' => self::mappingVersion($mappingVersion),
        ];
        if (null !== $entryCount) {
            $context['entry_count'] = max(0, $entryCount);
        }

        return new self(OpenAiResponsesDiagnostic::ResponseLimitExceeded, $context, $previous);
    }

    public static function unsupportedMapping(string $violation, string $field, ?int $entryPosition = null, ?Throwable $previous = null, OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1): self
    {
        $context = [
            'violation' => self::safeToken($violation, self::MAPPING_VIOLATIONS, 'unsupported_violation'),
            'field' => self::safeToken($field, self::FIELDS, 'unsupported_field'),
            'mapping_version' => self::mappingVersion($mappingVersion),
        ];
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return new self(OpenAiResponsesDiagnostic::ResponseMappingUnsupported, $context, $previous);
    }

    private static function mappingVersion(OpenAiResponsesMappingVersion $version): string
    {
        return match ($version) {
            OpenAiResponsesMappingVersion::Version1 => 'version_1',
            OpenAiResponsesMappingVersion::Version2 => 'version_2',
        };
    }
}
