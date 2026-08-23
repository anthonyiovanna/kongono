<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a rejected read-only request or a closed response-bound violation. */
#[Api(ApiStatus::Experimental)]
final class InvalidChieRequestException extends ChieException {}
