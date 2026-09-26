<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Condition\StorageMutationConditionKind;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;

/** Describes one inert create-only or version-matched replace request. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageWriteRequest
{
    public function __construct(
        private ContractVersion $contractVersion,
        private StorageOperationIdentity $operationIdentity,
        private StorageOperation $operation,
        private StorageObjectIdentity $objectIdentity,
        private StorageByteCount $declaredByteCount,
        private StorageContentDigest $contentDigest,
        private StorageMediaType $mediaType,
        private StorageMetadata $metadata,
        private StorageMutationCondition $condition,
    ) {
        if (!in_array($operation, [StorageOperation::Create, StorageOperation::Replace], true)) {
            throw InvalidStorageContractException::invalidRequest('unsupported_write_operation', $operation->value);
        }
        if (StorageOperation::Create === $operation && StorageMutationConditionKind::IfAbsent !== $condition->kind()) {
            throw InvalidStorageContractException::invalidMutationCondition('create_condition_relation', $operation->value);
        }
        if (StorageOperation::Replace === $operation && StorageMutationConditionKind::IfVersion !== $condition->kind()) {
            throw InvalidStorageContractException::invalidMutationCondition('replace_condition_relation', $operation->value);
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
        return $this->operation;
    }

    public function objectIdentity(): StorageObjectIdentity
    {
        return $this->objectIdentity;
    }

    public function declaredByteCount(): StorageByteCount
    {
        return $this->declaredByteCount;
    }

    public function contentDigest(): StorageContentDigest
    {
        return $this->contentDigest;
    }

    public function mediaType(): StorageMediaType
    {
        return $this->mediaType;
    }

    public function metadata(): StorageMetadata
    {
        return $this->metadata;
    }

    public function condition(): StorageMutationCondition
    {
        return $this->condition;
    }
}
