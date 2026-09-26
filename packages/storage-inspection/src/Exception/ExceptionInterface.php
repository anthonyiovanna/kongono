<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures owned by the storage-inspection package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
