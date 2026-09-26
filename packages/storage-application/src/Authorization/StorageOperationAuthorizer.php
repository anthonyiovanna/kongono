<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;

/** Application-owned authorization decision for one exact neutral request. */
#[Api(ApiStatus::Experimental)]
interface StorageOperationAuthorizer
{
    public function authorize(StorageReadRequest|StorageWriteRequest|StorageDeleteRequest|StorageListRequest $request): bool;
}
