<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Throwable;

/** Reports a bounded request mapping failure without exposing request values. */
#[Api(ApiStatus::Experimental)]
final class ResponsesRequestEncodingException extends OpenAiResponsesException
{
    private const array SURFACE_VIOLATIONS = ['contract_version', 'profile_mismatch', 'result_mode', 'streaming', 'input_role'];
    private const array ENCODING_VIOLATIONS = ['json_encoding'];
    private const array LIMIT_VIOLATIONS = ['request_body_bytes'];

    public static function unsupportedSurface(
        string $violation,
        ?int $entryPosition = null,
        ?Throwable $previous = null,
        OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1,
    ): self {
        $context = ['violation' => self::safeToken($violation, self::SURFACE_VIOLATIONS, 'unsupported_violation'), 'mapping_version' => self::mappingVersion($mappingVersion)];
        if (null !== $entryPosition) {
            $context['entry_position'] = max(0, $entryPosition);
        }

        return new self(OpenAiResponsesDiagnostic::UnsupportedMappingSurface, $context, $previous);
    }

    public static function encodingFailed(
        string $violation,
        ?Throwable $previous = null,
        OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1,
    ): self {
        return new self(OpenAiResponsesDiagnostic::RequestEncodingFailed, [
            'violation' => self::safeToken($violation, self::ENCODING_VIOLATIONS, 'unsupported_violation'),
            'mapping_version' => self::mappingVersion($mappingVersion),
        ], $previous);
    }

    public static function limitExceeded(
        string $violation,
        int $limit,
        ?Throwable $previous = null,
        OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1,
    ): self {
        return new self(OpenAiResponsesDiagnostic::RequestLimitExceeded, [
            'violation' => self::safeToken($violation, self::LIMIT_VIOLATIONS, 'unsupported_violation'),
            'limit' => max(0, $limit),
            'mapping_version' => self::mappingVersion($mappingVersion),
        ], $previous);
    }

    private static function mappingVersion(OpenAiResponsesMappingVersion $version): string
    {
        return match ($version) {
            OpenAiResponsesMappingVersion::Version1 => 'version_1',
            OpenAiResponsesMappingVersion::Version2 => 'version_2',
        };
    }
}
