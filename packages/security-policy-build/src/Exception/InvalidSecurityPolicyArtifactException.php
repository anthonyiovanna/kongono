<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports malformed or internally inconsistent policy artifact bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidSecurityPolicyArtifactException extends SecurityPolicyBuildException {}
