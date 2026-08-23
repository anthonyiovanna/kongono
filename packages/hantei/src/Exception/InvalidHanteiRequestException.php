<?php

declare(strict_types=1);

namespace Kongono\Hantei\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a rejected request at the fixed read-only Hantei boundary. */
#[Api(ApiStatus::Experimental)]
final class InvalidHanteiRequestException extends HanteiException {}
