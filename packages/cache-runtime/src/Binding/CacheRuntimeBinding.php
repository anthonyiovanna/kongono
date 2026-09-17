<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Binding;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\AdapterContractVersion;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactValidator;
use Kongono\CacheBuild\Exception\StaleCacheProfileArtifactException;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use Throwable;

/** Binds one exact-current selected profile to one directly supplied adapter. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheRuntimeBinding
{
    private CacheProfileDeclaration $profile;

    public function __construct(
        CacheProfileSet $currentProfiles,
        string $artifactBytes,
        CacheProfileName $selectedProfile,
        CacheAdapterIdentity $expectedAdapterIdentity,
        private CacheAdapter $adapter,
    ) {
        try {
            $artifact = (new CacheProfileArtifactValidator())->validateCurrent($currentProfiles, $artifactBytes);
            $profile = $artifact->profiles()->profile($selectedProfile);
        } catch (Error $failure) {
            throw $failure;
        } catch (StaleCacheProfileArtifactException $failure) {
            throw InvalidCacheRuntimeException::invalidBinding('artifact_stale', $failure);
        } catch (Throwable $failure) {
            throw InvalidCacheRuntimeException::invalidBinding('artifact_invalid', $failure);
        }

        if (!$profile instanceof CacheProfileDeclaration) {
            throw InvalidCacheRuntimeException::invalidBinding('profile_missing');
        }

        try {
            match ($adapter->protocolVersion()) {
                AdapterContractVersion::Version1 => null,
            };
            if (!$adapter->identity()->equals($expectedAdapterIdentity)) {
                throw InvalidCacheRuntimeException::invalidBinding('adapter_identity_mismatch');
            }
            $limits = $adapter->limits();
            if ($limits->maximumValueBytes() < $profile->valueLimit()->bytes()
                || $limits->maximumAggregateValueBytes() < $profile->valueLimit()->bytes()) {
                throw InvalidCacheRuntimeException::invalidBinding(
                    'adapter_limits',
                    byteCount: $profile->valueLimit()->bytes(),
                    limit: min($limits->maximumValueBytes(), $limits->maximumAggregateValueBytes()),
                );
            }
        } catch (Error $failure) {
            throw $failure;
        } catch (InvalidCacheRuntimeException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw InvalidCacheRuntimeException::invalidBinding('binding_failure', $failure);
        }

        $this->profile = $profile;
    }

    public function profile(): CacheProfileDeclaration
    {
        return $this->profile;
    }

    public function adapter(): CacheAdapter
    {
        return $this->adapter;
    }
}
