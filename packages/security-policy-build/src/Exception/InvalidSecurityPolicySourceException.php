<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports rejected policy source or application-root state. */
#[Api(ApiStatus::Experimental)]
final class InvalidSecurityPolicySourceException extends SecurityPolicyBuildException {}
