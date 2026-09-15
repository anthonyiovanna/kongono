<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\ContractVersion;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use Kongono\AssetResolution\Identity\AssetResolutionArtifactIdentity;
use Kongono\AssetResolution\Internal\ResolutionGuard;

/** Carries one canonical immutable native-or-adapter resolution artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetResolutionArtifact
{
    /** @var list<AssetResolutionEntry> */
    private array $entries;
    private string $bytes;
    private AssetResolutionArtifactIdentity $identity;

    /** @param array<array-key, mixed> $entries */
    public function __construct(
        private ContractVersion $contractVersion,
        private string $producerKind,
        private string $buildIdentity,
        private string $sourceSetIdentity,
        private string $graphIdentity,
        array $entries,
    ) {
        if (!in_array($producerKind, ['native', 'adapter'], true)) {
            throw InvalidAssetResolutionException::invalidArtifact('producer_kind');
        }
        ResolutionGuard::sha256($buildIdentity, 'build_identity_relation');
        ResolutionGuard::sha256($sourceSetIdentity, 'source_set_identity_relation');
        ResolutionGuard::sha256($graphIdentity, 'graph_identity_relation');
        if (!array_is_list($entries) || count($entries) > 1024) {
            throw InvalidAssetResolutionException::invalidArtifact('entry_list');
        }
        $byIdentity = [];
        $objects = [];
        $relations = 0;
        foreach ($entries as $entry) {
            if (!$entry instanceof AssetResolutionEntry) {
                throw InvalidAssetResolutionException::invalidArtifact('entry_type');
            }
            $logical = $entry->identity()->value();
            if (isset($byIdentity[$logical])) {
                throw InvalidAssetResolutionException::invalidArtifact('duplicate_entry');
            }
            $byIdentity[$logical] = $entry;
            $objects[$entry->primary()->objectKey()] = true;
            foreach ($entry->dependencies() as $dependency) {
                $objects[$dependency['object']->objectKey()] = true;
                ++$relations;
            }
        }
        if (count($objects) > 2048 || $relations > 4096) {
            throw InvalidAssetResolutionException::invalidArtifact('artifact_bound');
        }
        ksort($byIdentity, SORT_STRING);
        $this->entries = array_values($byIdentity);
        $this->bytes = ResolutionGuard::canonicalArtifactBytes($contractVersion, $producerKind, $buildIdentity, $sourceSetIdentity, $graphIdentity, $this->entries);
        $this->identity = AssetResolutionArtifactIdentity::fromArtifactBytes($this->bytes);
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function producerKind(): string
    {
        return $this->producerKind;
    }

    public function buildIdentity(): string
    {
        return $this->buildIdentity;
    }

    public function sourceSetIdentity(): string
    {
        return $this->sourceSetIdentity;
    }

    public function graphIdentity(): string
    {
        return $this->graphIdentity;
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function identity(): AssetResolutionArtifactIdentity
    {
        return $this->identity;
    }

    /** @return list<AssetResolutionEntry> */
    public function entries(): array
    {
        return [...$this->entries];
    }

    public function entry(LogicalAssetIdentity $identity): ?AssetResolutionEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->identity()->equals($identity)) {
                return $entry;
            }
        }

        return null;
    }

    public function entryCount(): int
    {
        return count($this->entries);
    }
}
