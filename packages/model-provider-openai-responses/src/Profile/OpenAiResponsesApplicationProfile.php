<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;

/** Carries the exact three current component profiles without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesApplicationProfile
{
    /** @internal Constructed only by the package profile factory. */
    public function __construct(
        private OpenAiResponsesProfile $providerProfile,
        private ModelInvocationApplicationProfile $modelInvocationProfile,
        private ExternalServiceApplicationProfile $externalServiceProfile,
        private OpenAiResponsesApplicationProfileIdentity $identity,
    ) {}

    public function providerProfile(): OpenAiResponsesProfile
    {
        return $this->providerProfile;
    }

    public function modelInvocationProfile(): ModelInvocationApplicationProfile
    {
        return $this->modelInvocationProfile;
    }

    public function externalServiceProfile(): ExternalServiceApplicationProfile
    {
        return $this->externalServiceProfile;
    }

    public function identity(): OpenAiResponsesApplicationProfileIdentity
    {
        return $this->identity;
    }
}
