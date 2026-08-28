<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports atomic publication or no-clobber failures. */
#[Api(ApiStatus::Experimental)]
final class ObservabilityPublicationException extends ObservabilityBuildException {}
