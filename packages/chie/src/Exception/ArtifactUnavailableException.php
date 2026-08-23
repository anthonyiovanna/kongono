<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports that the explicit artifact or current source root cannot be read safely. */
#[Api(ApiStatus::Experimental)]
final class ArtifactUnavailableException extends ChieException {}
