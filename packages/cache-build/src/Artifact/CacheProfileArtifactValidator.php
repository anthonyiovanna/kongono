<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Build\CacheProfileBuildIdentity;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;
use Kongono\CacheBuild\Exception\StaleCacheProfileArtifactException;
use Kongono\CacheBuild\Internal\BuildGuard;
use Kongono\CacheBuild\Internal\CanonicalJson;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileSetIdentity;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use Throwable;

/** Strictly validates canonical artifact structure and exact supplied currentness. */
#[Api(ApiStatus::Experimental)]
final class CacheProfileArtifactValidator
{
    public function validateStructure(string $artifactBytes): CacheProfileArtifact
    {
        try {
            $model = CanonicalJson::decode($artifactBytes);
            if (!hash_equals(CanonicalJson::encode($model), $artifactBytes)) {
                throw InvalidCacheProfileArtifactException::invalid('artifact_canonical');
            }
            BuildGuard::exactKeys($model, ['schema', 'source_identity', 'build_identity', 'profile_count', 'profiles']);
            if (BuildGuard::BUILD_PROFILE !== $model['schema']) {
                throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
            }
            $sourceIdentity = new CacheProfileSetIdentity($this->string($model['source_identity']));
            $buildIdentity = new CacheProfileBuildIdentity($this->string($model['build_identity']));
            $profileCount = $this->integer($model['profile_count']);
            $rows = $this->list($model['profiles']);
            if ($profileCount !== count($rows) || $profileCount > 256) {
                throw InvalidCacheProfileArtifactException::invalid('profile_count_relation');
            }
            $profiles = [];
            $previous = null;
            foreach ($rows as $row) {
                $item = $this->object($row);
                BuildGuard::exactKeys($item, ['name', 'contract_version', 'namespace', 'codec', 'lifetime_microseconds', 'max_value_bytes']);
                $nameValue = $this->string($item['name']);
                if (null !== $previous && strcmp($previous, $nameValue) >= 0) {
                    throw InvalidCacheProfileArtifactException::invalid('profile_order');
                }
                $previous = $nameValue;
                if (1 !== $this->integer($item['contract_version'])) {
                    throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
                }
                $codec = $this->object($item['codec']);
                BuildGuard::exactKeys($codec, ['name', 'version']);
                $profiles[] = new CacheProfileDeclaration(
                    ContractVersion::Version1,
                    new CacheProfileName($nameValue),
                    new CacheNamespace($this->string($item['namespace'])),
                    new CacheCodecIdentity($this->string($codec['name']), $this->integer($codec['version'])),
                    new CacheLifetime($this->integer($item['lifetime_microseconds'])),
                    new CacheProfileValueLimit($this->integer($item['max_value_bytes'])),
                );
            }
            $set = new CacheProfileSet($profiles);
            if (!$sourceIdentity->equals($set->identity())
                || !$buildIdentity->equals(CacheProfileBuildIdentity::fromSourceIdentity($sourceIdentity))) {
                throw InvalidCacheProfileArtifactException::invalid('artifact_identity_relation');
            }

            return new CacheProfileArtifact(
                $artifactBytes,
                $sourceIdentity,
                $buildIdentity,
                CacheProfileArtifactIdentity::fromBytes($artifactBytes),
                $set,
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidCacheProfileArtifactException) {
                throw $failure;
            }
            throw InvalidCacheProfileArtifactException::invalid('artifact_schema', $failure);
        }
    }

    public function validateCurrent(CacheProfileSet $currentProfiles, string $artifactBytes): CacheProfileArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new CacheProfileBuilder())->build($currentProfiles)->artifact();
        if (!$artifact->equals($current)) {
            throw StaleCacheProfileArtifactException::stale();
        }

        return $artifact;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
        }

        $copy = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
            }
            $copy[$key] = $entry;
        }

        return $copy;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
        }

        return $value;
    }

    private function integer(mixed $value): int
    {
        if (!is_int($value)) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
        }

        return $value;
    }
}
