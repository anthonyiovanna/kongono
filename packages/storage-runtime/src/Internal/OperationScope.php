<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use LogicException;

/** Carries and explicitly retires one runtime operation capability. */
#[Api(ApiStatus::Internal)]
final class OperationScope
{
    private bool $retired = false;

    public function __construct(
        private readonly RuntimeGuard $guard,
        private readonly StorageOperationIdentity $identity,
        private readonly StorageOperation $operation,
    ) {}

    public function identity(): StorageOperationIdentity
    {
        return $this->identity;
    }
    public function operation(): StorageOperation
    {
        return $this->operation;
    }
    public function retired(): bool
    {
        return $this->retired;
    }

    public function retire(): void
    {
        if ($this->retired) {
            throw new LogicException('Storage runtime operation scope is already retired.');
        }
        $this->guard->release($this);
        $this->retired = true;
    }
}
