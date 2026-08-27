<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\AuthorizationDecision;

/** Evaluates one exact registered policy against one fully explicit request. */
#[Api(ApiStatus::Experimental)]
interface AuthorizationEvaluator
{
    public function policy(): AuthorizationPolicyReference;

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision;
}
