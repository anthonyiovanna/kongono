<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every deliberate storage-contract construction failure. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
