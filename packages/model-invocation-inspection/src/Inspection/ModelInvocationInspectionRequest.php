<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationWiring;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;

/** Retains only one explicit profile, wiring, and optional detached completed result. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationInspectionRequest
{
    public function __construct(
        private ModelInvocationApplicationProfile $profile,
        private GeneratedModelInvocationWiring $wiring,
        private ?InvocationRuntimeResult $runtimeResult = null,
    ) {}

    public function profile(): ModelInvocationApplicationProfile
    {
        return $this->profile;
    }

    public function wiring(): GeneratedModelInvocationWiring
    {
        return $this->wiring;
    }

    public function runtimeResult(): ?InvocationRuntimeResult
    {
        return $this->runtimeResult;
    }
}
