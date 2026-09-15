<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\AdapterContractVersion;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetBuild\Source\AssetSourceSet;

/** Carries detached immutable evidence for one explicit adapter build. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterBuildRequest
{
    public function __construct(private AdapterContractVersion $contractVersion, private AssetSourceSet $sourceSet, private AdapterProfileIdentity $profileIdentity, private ToolchainIdentity $toolchainIdentity, private AdapterBuildLimits $limits) {}
    public function contractVersion(): AdapterContractVersion
    {
        return $this->contractVersion;
    }
    public function sourceSet(): AssetSourceSet
    {
        return $this->sourceSet;
    }
    public function profileIdentity(): AdapterProfileIdentity
    {
        return $this->profileIdentity;
    }
    public function toolchainIdentity(): ToolchainIdentity
    {
        return $this->toolchainIdentity;
    }
    public function limits(): AdapterBuildLimits
    {
        return $this->limits;
    }
}
