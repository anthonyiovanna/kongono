<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageApplication\Authorization\StorageOperationAuthorizer;
use Kongono\StorageApplication\Exception\StorageRuntimeCompositionException;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageRuntime\Binding\StorageRuntimeBinding;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Throwable;

/** Binds directly supplied exact-current evidence, adapter and application policy. */
#[Api(ApiStatus::Experimental)]
final class StorageApplicationRuntimeFactory
{
    public function create(
        CurrentStorageApplicationBuild $current,
        StorageProfileName $selectedProfile,
        StorageAdapterIdentity $expectedAdapterIdentity,
        StorageAdapter $adapter,
        StorageOperationAuthorizer $authorizer,
    ): AuthorizedStorageRuntime {
        try {
            $artifact = (new StorageProfileArtifactValidator())->validateCurrent($current->profiles(), $current->artifact()->bytes());
            if (!$artifact->equals($current->artifact())) {
                throw StorageRuntimeCompositionException::failed();
            }
            $binding = new StorageRuntimeBinding($current->profiles(), $artifact->bytes(), $selectedProfile, $expectedAdapterIdentity, $adapter);
            return new AuthorizedStorageRuntime(new ExactCurrentStorageRuntime($binding), $authorizer);
        } catch (Throwable $failure) {
            if ($failure instanceof StorageRuntimeCompositionException) {
                throw $failure;
            }
            throw StorageRuntimeCompositionException::failed($failure);
        }
    }
}
