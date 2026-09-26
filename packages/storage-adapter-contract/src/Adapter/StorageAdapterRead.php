<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Internal\AdapterContractGuard;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSource;

/** Relates one read result to its explicitly caller-owned open source. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageAdapterRead
{
    public function __construct(
        private StorageReadRequest $request,
        private StorageReadResult $result,
        private ?StorageByteSource $source = null,
    ) {
        AdapterContractGuard::read($request, $result, $source);
    }

    public function request(): StorageReadRequest
    {
        return $this->request;
    }

    public function result(): StorageReadResult
    {
        return $this->result;
    }

    public function source(): ?StorageByteSource
    {
        return $this->source;
    }
}
