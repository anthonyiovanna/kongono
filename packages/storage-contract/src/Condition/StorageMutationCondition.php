<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Condition;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Version\StorageObjectVersion;

/** Carries an explicit create-only or exact-version mutation condition. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageMutationCondition
{
    private function __construct(
        private StorageMutationConditionKind $kind,
        private ?StorageObjectVersion $version,
    ) {}

    public static function ifAbsent(): self
    {
        return new self(StorageMutationConditionKind::IfAbsent, null);
    }

    public static function ifVersion(StorageObjectVersion $version): self
    {
        return new self(StorageMutationConditionKind::IfVersion, $version);
    }

    public function kind(): StorageMutationConditionKind
    {
        return $this->kind;
    }

    public function version(): ?StorageObjectVersion
    {
        return $this->version;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind
            && ((null === $this->version && null === $other->version)
                || (null !== $this->version && null !== $other->version && $this->version->equals($other->version)));
    }
}
