<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAdapterBuildArtifactException;
use Kongono\AssetAdapterContract\Identity\AdapterBuildIdentity;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetBuild\Graph\AssetGraphIdentity;
use Kongono\AssetBuild\Source\AssetSourceSetIdentity;

/** Carries one completely validated canonical adapter-build artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterBuildArtifact
{
    public function __construct(private string $bytes, private AdapterBuildIdentity $buildIdentity, private AssetSourceSetIdentity $sourceSetIdentity, private AssetGraphIdentity $graphIdentity, private AdapterProfileIdentity $profileIdentity, private ToolchainIdentity $toolchainIdentity, private string $adapterPackage, private int $adapterVersion, private int $bindingCount, private int $dependencyCount, private int $outputCount)
    {
        if (!$buildIdentity->equals(AdapterBuildIdentity::fromArtifactBytes($bytes)) || $adapterVersion !== 1 || 1 !== preg_match('/\Akongono\/[a-z][a-z0-9-]{0,63}\z/D', $adapterPackage) || min($bindingCount, $dependencyCount, $outputCount) < 0) {
            throw InvalidAdapterBuildArtifactException::invalid('artifact_identity_relation');
        }
    }
    public function bytes(): string
    {
        return $this->bytes;
    }public function buildIdentity(): AdapterBuildIdentity
    {
        return $this->buildIdentity;
    }public function sourceSetIdentity(): AssetSourceSetIdentity
    {
        return $this->sourceSetIdentity;
    }public function graphIdentity(): AssetGraphIdentity
    {
        return $this->graphIdentity;
    }public function profileIdentity(): AdapterProfileIdentity
    {
        return $this->profileIdentity;
    }public function toolchainIdentity(): ToolchainIdentity
    {
        return $this->toolchainIdentity;
    }public function adapterPackage(): string
    {
        return $this->adapterPackage;
    }public function adapterVersion(): int
    {
        return $this->adapterVersion;
    }public function bindingCount(): int
    {
        return $this->bindingCount;
    }public function dependencyCount(): int
    {
        return $this->dependencyCount;
    }public function outputCount(): int
    {
        return $this->outputCount;
    }public function equals(self $other): bool
    {
        return hash_equals($this->bytes, $other->bytes) && $this->buildIdentity->equals($other->buildIdentity);
    }
}
