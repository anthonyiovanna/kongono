<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;

/** Binds one inert bounded Version 1 storage-profile declaration. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileDeclaration
{
    private ContractVersion $contractVersion;

    public function __construct(
        ContractVersion $contractVersion,
        private StorageProfileName $name,
        private StorageNamespace $namespace,
        private StorageCapabilitySet $capabilities,
        private ?StorageDigestAlgorithm $digestAlgorithm,
        private StorageProfileLimits $limits,
    ) {
        $this->contractVersion = match ($contractVersion) {
            ContractVersion::Version1 => $contractVersion,
        };
        if ([] === $capabilities->capabilities()) {
            throw InvalidStorageProfileException::invalid('empty_capabilities');
        }
        if ($capabilities->contains(StorageCapability::ContentDigest) !== (null !== $digestAlgorithm)) {
            throw InvalidStorageProfileException::invalid('digest_capability_relation');
        }
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function name(): StorageProfileName
    {
        return $this->name;
    }

    public function namespace(): StorageNamespace
    {
        return $this->namespace;
    }

    public function capabilities(): StorageCapabilitySet
    {
        return $this->capabilities;
    }

    public function digestAlgorithm(): ?StorageDigestAlgorithm
    {
        return $this->digestAlgorithm;
    }

    public function limits(): StorageProfileLimits
    {
        return $this->limits;
    }

    public function equals(self $other): bool
    {
        return $this->name->equals($other->name)
            && $this->namespace->equals($other->namespace)
            && $this->capabilities->equals($other->capabilities)
            && $this->digestAlgorithm === $other->digestAlgorithm
            && $this->limits->equals($other->limits);
    }
}
