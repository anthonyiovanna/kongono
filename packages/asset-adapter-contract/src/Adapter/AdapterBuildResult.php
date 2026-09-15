<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifact;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;

/** Carries one validated artifact, output graph, and complete object-byte map. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterBuildResult
{
    /** @var array<string, string> */
    private array $outputBytesByPath;

    /** @param array<array-key, mixed> $outputBytesByPath */
    public function __construct(private AdapterBuildArtifact $artifact, private AdapterOutputSet $outputSet, array $outputBytesByPath)
    {
        $map = AdapterContractGuard::exactStringMap($outputBytesByPath);
        [$validated, $set] = (new AdapterBuildArtifactValidator())->validate($outputSet->sourceSet(), $artifact->profileIdentity(), $artifact->toolchainIdentity(), $artifact->bytes(), $map, $outputSet->maximumDepth());
        if (!$validated->equals($artifact) || $set->outputCount() !== $outputSet->outputCount()) {
            throw InvalidAssetAdapterContractException::output('output_metadata_relation');
        }
        $this->outputBytesByPath = $map;
    }
    public function artifact(): AdapterBuildArtifact
    {
        return $this->artifact;
    }

    public function outputSet(): AdapterOutputSet
    {
        return $this->outputSet;
    }

    /** @return array<string, string> */
    public function outputBytesByPath(): array
    {
        return $this->outputBytesByPath;
    }
}
