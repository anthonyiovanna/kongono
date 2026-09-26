<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Exception\StorageApplicationWorkflowException;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactValidator;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Throwable;

/** Carries checked exact-current storage-profile evidence for explicit composition. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentStorageApplicationBuild
{
    public function __construct(private StorageProfileSet $profiles, private StorageProfileArtifact $artifact)
    {
        try {
            $validated = (new StorageProfileArtifactValidator())->validateCurrent($profiles, $artifact->bytes());
            if (!$validated->equals($artifact)) {
                throw StorageApplicationWorkflowException::workflow('currentness');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof StorageApplicationWorkflowException) {
                throw $failure;
            }
            throw StorageApplicationWorkflowException::workflow('currentness', $failure);
        }
    }
    public function profiles(): StorageProfileSet
    {
        return $this->profiles;
    }
    public function artifact(): StorageProfileArtifact
    {
        return $this->artifact;
    }
}
