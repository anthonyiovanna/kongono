<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports an explicit inspect, build, or check workflow failure. */
#[Api(ApiStatus::Experimental)]
final class ObservabilityWorkflowException extends ObservabilityBuildException {}
