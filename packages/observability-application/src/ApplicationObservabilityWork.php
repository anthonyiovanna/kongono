<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Executes one typed unit of application work through its exact observability operation.
 *
 * @template-covariant TResult
 */
#[Api(ApiStatus::Experimental)]
interface ApplicationObservabilityWork
{
    /** @return TResult */
    public function execute(ApplicationObservabilityOperation $operation): mixed;
}
