<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines one explicit caller-owned sampling decision capability. */
#[Api(ApiStatus::Experimental)]
interface TraceSampler
{
    public function decide(TraceSamplingRequest $request): TraceSamplingDecision;
}
