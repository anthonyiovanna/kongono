<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationException;
use LogicException;

/** Owns the minimum instance-local single-active-operation state. */
#[Api(ApiStatus::Internal)]
final class RuntimeGuard
{
    private ?OperationScope $active = null;

    public function enter(StorageOperationIdentity $identity, StorageOperation $operation): OperationScope
    {
        if (null !== $this->active) {
            throw StorageRuntimeOperationException::reentrant($operation->value);
        }
        $scope = new OperationScope($this, $identity, $operation);
        $this->active = $scope;

        return $scope;
    }

    public function release(OperationScope $scope): void
    {
        if ($this->active !== $scope) {
            throw new LogicException('Storage runtime scope does not own the active operation.');
        }
        $this->active = null;
    }
}
