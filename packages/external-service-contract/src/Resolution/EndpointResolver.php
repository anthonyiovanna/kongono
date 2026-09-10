<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Resolution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;

/** Establishes Phase 15 resolver compatibility without adding resolution behavior. */
#[Api(ApiStatus::Experimental)]
interface EndpointResolver extends EgressResolver {}
