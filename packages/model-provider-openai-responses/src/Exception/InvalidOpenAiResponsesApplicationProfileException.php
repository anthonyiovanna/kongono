<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Diagnostic\OpenAiResponsesDiagnostic;
use Throwable;

/** Reports exact-current application-profile rejection without exposing inputs. */
#[Api(ApiStatus::Experimental)]
final class InvalidOpenAiResponsesApplicationProfileException extends OpenAiResponsesException
{
    private const array VIOLATIONS = [
        'profile_identity_grammar',
        'model_application_profile',
        'external_service_application_profile',
        'adapter_identity',
        'neutral_profile',
        'provider_identity',
        'endpoint_scheme',
        'endpoint_base_path',
        'mapping_surface',
        'component_identity',
        'loaded_profile',
        'factory_profile',
    ];

    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesDiagnostic::InvalidApplicationProfile, [
            'violation' => self::safeToken($violation, self::VIOLATIONS, 'unsupported_violation'),
        ], $previous);
    }
}
