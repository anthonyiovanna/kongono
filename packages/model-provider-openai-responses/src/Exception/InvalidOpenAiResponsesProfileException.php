<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Throwable;

/** Reports one invalid exact provider profile without exposing profile values. */
#[Api(ApiStatus::Experimental)]
final class InvalidOpenAiResponsesProfileException extends OpenAiResponsesException
{
    private const array VIOLATIONS = ['contract_version', 'provider_identity', 'capability_set', 'structured_output_name'];

    public static function invalidProfile(
        string $violation,
        ?Throwable $previous = null,
        OpenAiResponsesMappingVersion $mappingVersion = OpenAiResponsesMappingVersion::Version1,
    ): self {
        return new self(OpenAiResponsesDiagnostic::InvalidProviderProfile, [
            'violation' => self::safeToken($violation, self::VIOLATIONS, 'unsupported_violation'),
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
