<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;

/** Describes one inert bounded read request without provider authority. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageReadRequest
{
    public function __construct(
        private ContractVersion $contractVersion,
        private StorageOperationIdentity $operationIdentity,
        private StorageObjectIdentity $objectIdentity,
        private StorageByteCount $maximumByteCount,
    ) {}

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function operationIdentity(): StorageOperationIdentity
    {
        return $this->operationIdentity;
    }

    public function operation(): StorageOperation
    {
        return StorageOperation::Read;
    }

    public function objectIdentity(): StorageObjectIdentity
    {
        return $this->objectIdentity;
    }

    public function maximumByteCount(): StorageByteCount
    {
        return $this->maximumByteCount;
    }
}
