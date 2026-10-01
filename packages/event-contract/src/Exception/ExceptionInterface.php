<?php

declare(strict_types=1);

namespace Kongono\EventContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks deliberate neutral event-contract failures. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
