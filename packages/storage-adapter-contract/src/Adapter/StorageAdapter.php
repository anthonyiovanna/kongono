<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Stream\StorageByteSource;

/** Defines one directly supplied bounded storage adapter. */
#[Api(ApiStatus::Experimental)]
interface StorageAdapter
{
    public function descriptor(): StorageAdapterDescriptor;

    public function read(StorageReadRequest $request): StorageAdapterRead;

    public function write(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult;

    public function delete(StorageDeleteRequest $request): StorageMutationResult;

    public function list(StorageListRequest $request): StorageListResult;
}
