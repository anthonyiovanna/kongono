<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports structurally valid evidence that does not match current source state. */
#[Api(ApiStatus::Experimental)]
final class StaleObservabilityArtifactException extends ObservabilityBuildException {}
