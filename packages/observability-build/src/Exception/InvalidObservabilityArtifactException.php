<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports malformed or internally inconsistent observability artifact bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidObservabilityArtifactException extends ObservabilityBuildException {}
