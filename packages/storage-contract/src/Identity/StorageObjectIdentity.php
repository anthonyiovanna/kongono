<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Composes logical identity domains without constructing a path or provider key. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageObjectIdentity
{
    public function __construct(
        private StorageNamespace $namespace,
        private LogicalObjectIdentity $logicalIdentity,
    ) {}

    public function namespace(): StorageNamespace
    {
        return $this->namespace;
    }

    public function logicalIdentity(): LogicalObjectIdentity
    {
        return $this->logicalIdentity;
    }

    public function equals(self $other): bool
    {
        return $this->namespace->equals($other->namespace)
            && $this->logicalIdentity->equals($other->logicalIdentity);
    }
}
