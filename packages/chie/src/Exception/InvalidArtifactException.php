<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports malformed, noncanonical, tampered, or internally invalid artifact data. */
#[Api(ApiStatus::Experimental)]
final class InvalidArtifactException extends ChieException {}
