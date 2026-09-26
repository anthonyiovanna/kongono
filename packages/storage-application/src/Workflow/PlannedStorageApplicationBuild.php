<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Exception\StorageApplicationInvariantException;

/** Carries bounded write-free expected storage-profile evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedStorageApplicationBuild
{
    public function __construct(
        private string $setIdentity,
        private string $buildIdentity,
        private string $artifactIdentity,
        private int $profileCount,
        private bool $publicationRequired,
    ) {
        foreach ([$setIdentity, $buildIdentity, $artifactIdentity] as $identity) {
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $identity)) {
                throw StorageApplicationInvariantException::failed('plan_relation');
            }
        }
        if ($profileCount < 1 || $profileCount > 256) {
            throw StorageApplicationInvariantException::failed('plan_relation');
        }
    }
    public function setIdentity(): string
    {
        return $this->setIdentity;
    }
    public function buildIdentity(): string
    {
        return $this->buildIdentity;
    }
    public function artifactIdentity(): string
    {
        return $this->artifactIdentity;
    }
    public function profileCount(): int
    {
        return $this->profileCount;
    }
    public function publicationRequired(): bool
    {
        return $this->publicationRequired;
    }
}
