<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports that a structurally valid artifact does not match the explicit root. */
#[Api(ApiStatus::Experimental)]
final class ArtifactStaleException extends ChieException {}
