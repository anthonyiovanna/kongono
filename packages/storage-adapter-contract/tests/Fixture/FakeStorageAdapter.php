<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Fixture;

use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSource;

final readonly class FakeStorageAdapter implements StorageAdapter
{
    public function __construct(private StorageAdapterDescriptor $descriptor) {}
    public function descriptor(): StorageAdapterDescriptor
    {
        return $this->descriptor;
    }
    public function read(StorageReadRequest $request): StorageAdapterRead
    {
        return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent));
    }
    public function write(StorageWriteRequest $request, StorageByteSource $source): StorageMutationResult
    {
        return new StorageMutationResult(StorageMutationOutcome::Unavailable);
    }
    public function delete(StorageDeleteRequest $request): StorageMutationResult
    {
        return new StorageMutationResult(StorageMutationOutcome::Unavailable);
    }
    public function list(StorageListRequest $request): StorageListResult
    {
        return new StorageListResult(StorageListOutcome::Unavailable);
    }
}
