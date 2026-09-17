<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every cache-adapter-contract exception for package-family catches. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
