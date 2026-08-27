<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports an explicit inspect, build, or check workflow failure. */
#[Api(ApiStatus::Experimental)]
final class SecurityPolicyWorkflowException extends SecurityPolicyBuildException {}
