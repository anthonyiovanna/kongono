<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Grant;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Internal\ExtensionGrantPlanValidator;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Binds one exact declared request to an explicit grant or denial decision. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityDecision
{
    private function __construct(
        private ExtensionIdentity $extensionIdentity,
        private ExtensionVersion $extensionVersion,
        private CapabilityIdentity $capabilityIdentity,
        private CapabilityPurpose $purpose,
        private CapabilityRequirement $requirement,
        private string $constructorParameter,
        private string $providerContract,
        private CapabilityDisposition $disposition,
        private ?CapabilityProviderLifetime $providerLifetime,
    ) {
        ExtensionGrantPlanValidator::parameter($constructorParameter);
        ExtensionGrantPlanValidator::symbol($providerContract);
    }

    public static function grant(
        ExtensionIdentity $extensionIdentity,
        ExtensionVersion $extensionVersion,
        CapabilityIdentity $capabilityIdentity,
        CapabilityPurpose $purpose,
        CapabilityRequirement $requirement,
        string $constructorParameter,
        string $providerContract,
        CapabilityProviderLifetime $providerLifetime,
    ): self {
        return new self($extensionIdentity, $extensionVersion, $capabilityIdentity, $purpose, $requirement, $constructorParameter, $providerContract, CapabilityDisposition::Granted, $providerLifetime);
    }

    public static function deny(
        ExtensionIdentity $extensionIdentity,
        ExtensionVersion $extensionVersion,
        CapabilityIdentity $capabilityIdentity,
        CapabilityPurpose $purpose,
        CapabilityRequirement $requirement,
        string $constructorParameter,
        string $providerContract,
    ): self {
        return new self($extensionIdentity, $extensionVersion, $capabilityIdentity, $purpose, $requirement, $constructorParameter, $providerContract, CapabilityDisposition::Denied, null);
    }

    public function extensionIdentity(): ExtensionIdentity
    {
        return $this->extensionIdentity;
    }
    public function extensionVersion(): ExtensionVersion
    {
        return $this->extensionVersion;
    }
    public function capabilityIdentity(): CapabilityIdentity
    {
        return $this->capabilityIdentity;
    }
    public function purpose(): CapabilityPurpose
    {
        return $this->purpose;
    }
    public function requirement(): CapabilityRequirement
    {
        return $this->requirement;
    }
    public function constructorParameter(): string
    {
        return $this->constructorParameter;
    }
    public function providerContract(): string
    {
        return $this->providerContract;
    }
    public function disposition(): CapabilityDisposition
    {
        return $this->disposition;
    }
    public function providerLifetime(): ?CapabilityProviderLifetime
    {
        return $this->providerLifetime;
    }
}
