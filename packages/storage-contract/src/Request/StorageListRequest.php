<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Pagination\StorageContinuationToken;

/** Describes one inert bounded namespace list request. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageListRequest
{
    public function __construct(
        private ContractVersion $contractVersion,
        private StorageOperationIdentity $operationIdentity,
        private StorageNamespace $namespace,
        private int $pageSize,
        private ?StorageContinuationToken $continuationToken = null,
    ) {
        if ($pageSize < 1) {
            throw InvalidStorageContractException::invalidRequest('page_size_range', StorageOperation::List->value);
        }
        if ($pageSize > StorageContractBounds::MAX_LIST_PAGE_SIZE) {
            throw InvalidStorageContractException::contractBoundExceeded('list_page_size', StorageContractBounds::MAX_LIST_PAGE_SIZE);
        }
    }

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
        return StorageOperation::List;
    }

    public function namespace(): StorageNamespace
    {
        return $this->namespace;
    }

    public function pageSize(): int
    {
        return $this->pageSize;
    }

    public function continuationToken(): ?StorageContinuationToken
    {
        return $this->continuationToken;
    }
}
