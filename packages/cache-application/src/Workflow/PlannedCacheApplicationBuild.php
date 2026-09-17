<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries bounded write-free expected cache-profile evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedCacheApplicationBuild
{
    public function __construct(
        private string $setIdentity,
        private string $buildIdentity,
        private string $artifactIdentity,
        private int $profileCount,
        private bool $publicationRequired,
    ) {}
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
