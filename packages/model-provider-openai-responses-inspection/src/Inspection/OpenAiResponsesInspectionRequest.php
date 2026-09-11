<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;

/** Carries one explicit provider application profile and optional detached terminal evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesInspectionRequest
{
    public function __construct(
        private OpenAiResponsesApplicationProfile $profile,
        private ?InvocationRuntimeResult $runtimeResult = null,
    ) {}

    public function profile(): OpenAiResponsesApplicationProfile
    {
        return $this->profile;
    }

    public function runtimeResult(): ?InvocationRuntimeResult
    {
        return $this->runtimeResult;
    }
}
