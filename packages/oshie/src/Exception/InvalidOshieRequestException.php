<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a request outside the closed Oshie grammar or response bound. */
#[Api(ApiStatus::Experimental)]
final class InvalidOshieRequestException extends OshieException {}
