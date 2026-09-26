<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Build\StorageProfileBuildIdentity;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Exception\StaleStorageProfileArtifactException;
use Kongono\StorageBuild\Internal\BuildGuard;
use Kongono\StorageBuild\Internal\CanonicalJson;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageBuild\Profile\StorageProfileSetIdentity;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;
use Throwable;

/** Strictly validates canonical artifact structure and exact supplied currentness. */
#[Api(ApiStatus::Experimental)]
final class StorageProfileArtifactValidator
{
    public function validateStructure(string $artifactBytes): StorageProfileArtifact
    {
        try {
            $model = CanonicalJson::decode($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($model), $artifactBytes)) {
                throw InvalidStorageProfileArtifactException::invalid('artifact_canonical');
            }
            BuildGuard::exactKeys($model, ['schema', 'source_identity', 'build_identity', 'profile_count', 'profiles']);
            if (BuildGuard::BUILD_PROFILE !== $model['schema']) {
                throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
            }
            $sourceIdentity = new StorageProfileSetIdentity($this->string($model['source_identity']));
            $buildIdentity = new StorageProfileBuildIdentity($this->string($model['build_identity']));
            $profileCount = $this->integer($model['profile_count']);
            $rows = $this->list($model['profiles']);
            if ($profileCount !== count($rows) || $profileCount < 1 || $profileCount > 256) {
                throw InvalidStorageProfileArtifactException::invalid('profile_count_relation');
            }
            $profiles = [];
            $previous = null;
            foreach ($rows as $row) {
                $item = $this->object($row);
                BuildGuard::exactKeys($item, ['name', 'contract_version', 'namespace', 'capabilities', 'digest_algorithm', 'limits']);
                $nameValue = $this->string($item['name']);
                if (null !== $previous && strcmp($previous, $nameValue) >= 0) {
                    throw InvalidStorageProfileArtifactException::invalid('profile_order');
                }
                $previous = $nameValue;
                if (ContractVersion::Version1->value !== $this->integer($item['contract_version'])) {
                    throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
                }
                $capabilities = [];
                foreach ($this->list($item['capabilities']) as $capability) {
                    $capabilities[] = StorageCapability::from($this->string($capability));
                }
                $digestAlgorithm = null;
                if (null !== $item['digest_algorithm']) {
                    $digestAlgorithm = StorageDigestAlgorithm::from($this->string($item['digest_algorithm']));
                }
                $limits = $this->object($item['limits']);
                BuildGuard::exactKeys($limits, ['max_chunk_bytes', 'max_object_bytes', 'max_list_page_size']);
                $profiles[] = new StorageProfileDeclaration(
                    ContractVersion::Version1,
                    new StorageProfileName($nameValue),
                    new StorageNamespace($this->string($item['namespace'])),
                    new StorageCapabilitySet($capabilities),
                    $digestAlgorithm,
                    new StorageProfileLimits(
                        $this->integer($limits['max_chunk_bytes']),
                        $this->integer($limits['max_object_bytes']),
                        $this->integer($limits['max_list_page_size']),
                    ),
                );
            }
            $set = new StorageProfileSet($profiles);
            $rebuilt = (new StorageProfileBuilder())->build($set)->artifact();
            if (!$sourceIdentity->equals($rebuilt->sourceIdentity())
                || !$buildIdentity->equals($rebuilt->buildIdentity())
                || !hash_equals($rebuilt->bytes(), $artifactBytes)) {
                throw InvalidStorageProfileArtifactException::invalid('artifact_identity_relation');
            }

            return $rebuilt;
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidStorageProfileArtifactException) {
                throw $failure;
            }
            throw InvalidStorageProfileArtifactException::invalid('artifact_schema', $failure);
        }
    }

    public function validateCurrent(StorageProfileSet $currentProfiles, string $artifactBytes): StorageProfileArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new StorageProfileBuilder())->build($currentProfiles)->artifact();
        if (!$artifact->equals($current)) {
            throw StaleStorageProfileArtifactException::stale();
        }

        return $artifact;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
        }

        $copy = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
            }
            $copy[$key] = $entry;
        }

        return $copy;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function integer(mixed $value): int
    {
        if (!is_int($value)) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
        }

        return $value;
    }
}
