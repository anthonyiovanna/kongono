<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Condition\StorageMutationConditionKind;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;

/** Describes one inert exact-version delete request. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageDeleteRequest
{
    public function __construct(
        private ContractVersion $contractVersion,
        private StorageOperationIdentity $operationIdentity,
        private StorageObjectIdentity $objectIdentity,
        private StorageMutationCondition $condition,
    ) {
        if (StorageMutationConditionKind::IfVersion !== $condition->kind()) {
            throw InvalidStorageContractException::invalidMutationCondition('delete_condition_relation', StorageOperation::Delete->value);
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
        return StorageOperation::Delete;
    }

    public function objectIdentity(): StorageObjectIdentity
    {
        return $this->objectIdentity;
    }

    public function condition(): StorageMutationCondition
    {
        return $this->condition;
    }
}
