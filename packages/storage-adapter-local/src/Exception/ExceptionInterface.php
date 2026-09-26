<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every local-storage-adapter exception for package-family catches. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
