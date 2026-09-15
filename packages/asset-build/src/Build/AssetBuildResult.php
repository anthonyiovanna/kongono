<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Artifact\AssetBuildArtifact;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetBuild\Internal\BuildGuard;

/** Carries one immutable validated artifact, logical outputs, and unique object bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetBuildResult
{
    /** @var list<AssetOutput> */
    private array $outputs;
    /** @var array<string, string> */
    private array $outputBytesByPath;

    /**
     * @param array<array-key, mixed> $outputs
     * @param array<array-key, mixed> $outputBytesByPath
     */
    public function __construct(private AssetBuildArtifact $artifact, array $outputs, array $outputBytesByPath)
    {
        if (!array_is_list($outputs)) {
            throw InvalidAssetBuildArtifactException::output('outputs_not_list');
        }
        $byIdentity = [];
        foreach ($outputs as $output) {
            if (!$output instanceof AssetOutput || isset($byIdentity[$output->identity()->value()])) {
                throw InvalidAssetBuildArtifactException::output('duplicate_output');
            }
            $byIdentity[$output->identity()->value()] = $output;
        }
        ksort($byIdentity, SORT_STRING);
        if (array_values($byIdentity) !== $outputs) {
            throw InvalidAssetBuildArtifactException::output('output_identity_relation');
        }
        $map = BuildGuard::exactStringMap($outputBytesByPath);
        $expectedPaths = [];
        foreach ($outputs as $output) {
            $path = $output->path()->value();
            if (!isset($map[$path]) || !hash_equals($map[$path], $output->bytes())) {
                throw InvalidAssetBuildArtifactException::output('output_evidence_relation');
            }
            $expectedPaths[$path] = true;
        }
        $paths = array_keys($expectedPaths);
        sort($paths, SORT_STRING);
        if ($paths !== array_keys($map) || $artifact->outputCount() !== count($outputs)) {
            throw InvalidAssetBuildArtifactException::output('output_evidence_relation');
        }
        $this->outputs = [...$outputs];
        $this->outputBytesByPath = $map;
    }

    public function artifact(): AssetBuildArtifact
    {
        return $this->artifact;
    }
    /** @return list<AssetOutput> */
    public function outputs(): array
    {
        return [...$this->outputs];
    }
    /** @return array<string, string> */
    public function outputBytesByPath(): array
    {
        return [...$this->outputBytesByPath];
    }
    public function outputCount(): int
    {
        return count($this->outputs);
    }
    public function objectCount(): int
    {
        return count($this->outputBytesByPath);
    }

    public function equals(self $other): bool
    {
        if (!$this->artifact->equals($other->artifact) || $this->outputBytesByPath !== $other->outputBytesByPath || count($this->outputs) !== count($other->outputs)) {
            return false;
        }
        foreach ($this->outputs as $position => $output) {
            if (!$output->equals($other->outputs[$position])) {
                return false;
            }
        }

        return true;
    }
}
