<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every storage-adapter-contract exception for package-family catches. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
