<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every bounded-memory-adapter exception for package-family catches. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
