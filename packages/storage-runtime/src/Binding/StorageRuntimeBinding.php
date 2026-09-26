<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Binding;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Exception\StaleStorageProfileArtifactException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use Throwable;

/** Binds one exact-current selected profile to one directly supplied adapter. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageRuntimeBinding
{
    private StorageProfileDeclaration $profile;

    public function __construct(
        StorageProfileSet $currentProfiles,
        string $artifactBytes,
        StorageProfileName $selectedProfile,
        StorageAdapterIdentity $expectedAdapterIdentity,
        private StorageAdapter $adapter,
    ) {
        try {
            $artifact = (new StorageProfileArtifactValidator())->validateCurrent($currentProfiles, $artifactBytes);
            $profile = $artifact->profiles()->profile($selectedProfile);
        } catch (Error $failure) {
            throw $failure;
        } catch (StaleStorageProfileArtifactException $failure) {
            throw InvalidStorageRuntimeException::binding('artifact_stale', $failure);
        } catch (Throwable $failure) {
            throw InvalidStorageRuntimeException::binding('artifact_invalid', $failure);
        }
        if (!$profile instanceof StorageProfileDeclaration) {
            throw InvalidStorageRuntimeException::binding('profile_missing');
        }

        try {
            $descriptor = $adapter->descriptor();
            if (!$descriptor->identity()->equals($expectedAdapterIdentity)) {
                throw InvalidStorageRuntimeException::binding('adapter_identity_mismatch');
            }
            if (!$descriptor->artifact()->equals($artifact)
                || !$descriptor->sourceIdentity()->equals($artifact->sourceIdentity())
                || !$descriptor->buildIdentity()->equals($artifact->buildIdentity())
                || !$descriptor->artifactIdentity()->equals($artifact->artifactIdentity())) {
                throw InvalidStorageRuntimeException::binding('descriptor_artifact_mismatch');
            }
            if (!$descriptor->profileName()->equals($selectedProfile)) {
                throw InvalidStorageRuntimeException::binding('descriptor_profile_name_mismatch');
            }
            if (!$descriptor->profile()->equals($profile)) {
                throw InvalidStorageRuntimeException::binding('descriptor_profile_mismatch');
            }
        } catch (Error $failure) {
            throw $failure;
        } catch (InvalidStorageRuntimeException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw InvalidStorageRuntimeException::binding('binding_failure', $failure);
        }

        $this->profile = $profile;
    }

    public function profile(): StorageProfileDeclaration
    {
        return $this->profile;
    }
    public function adapter(): StorageAdapter
    {
        return $this->adapter;
    }
}
