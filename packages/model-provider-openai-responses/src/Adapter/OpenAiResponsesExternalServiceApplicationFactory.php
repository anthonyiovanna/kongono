<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;

/** Creates one fresh Ready application matching one advertised exact profile. */
#[Api(ApiStatus::Experimental)]
interface OpenAiResponsesExternalServiceApplicationFactory
{
    public function profile(): ExternalServiceApplicationProfile;

    /**
     * Creation failure must clean up anything it started. Every successful call
     * returns a fresh Ready application whose profile is the advertised instance.
     */
    public function create(): ExternalServiceApplication;
}
