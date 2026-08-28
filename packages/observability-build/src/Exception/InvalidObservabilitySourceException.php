<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports rejected observability source or application-root state. */
#[Api(ApiStatus::Experimental)]
final class InvalidObservabilitySourceException extends ObservabilityBuildException {}
