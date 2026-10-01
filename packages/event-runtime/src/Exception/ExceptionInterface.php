<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks deliberate event-runtime failures without relabeling handler failures. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
