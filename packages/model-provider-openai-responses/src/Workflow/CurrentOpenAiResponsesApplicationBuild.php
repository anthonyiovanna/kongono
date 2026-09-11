<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Workflow\CurrentModelInvocationApplicationBuild;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplication;

/** Returns exact component evidence, the lower build, and one Ready application. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentOpenAiResponsesApplicationBuild
{
    public function __construct(
        private OpenAiResponsesApplicationProfile $profile,
        private CurrentModelInvocationApplicationBuild $modelInvocationBuild,
        private OpenAiResponsesApplication $application,
    ) {}

    public function profile(): OpenAiResponsesApplicationProfile
    {
        return $this->profile;
    }
    public function modelInvocationBuild(): CurrentModelInvocationApplicationBuild
    {
        return $this->modelInvocationBuild;
    }
    public function application(): OpenAiResponsesApplication
    {
        return $this->application;
    }
}
