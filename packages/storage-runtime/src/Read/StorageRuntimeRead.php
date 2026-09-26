<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Read;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;

/** Relates validated neutral read evidence to its runtime-controlled source. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageRuntimeRead
{
    public function __construct(private StorageReadResult $result, private ?StorageByteSource $source = null)
    {
        if ((StorageReadOutcome::Completed === $result->outcome()) !== (null !== $source)) {
            throw InvalidStorageRuntimeException::operation('read_source_relation', 'read');
        }
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
