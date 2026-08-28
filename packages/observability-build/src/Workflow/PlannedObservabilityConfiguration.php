<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports only bounded predicted observability output identities. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedObservabilityConfiguration
{
    public function __construct(
        private string $artifactBasename,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $profile,
        private bool $publicationRequired,
    ) {}

    public function artifactBasename(): string
    {
        return $this->artifactBasename;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function publicationRequired(): bool
    {
        return $this->publicationRequired;
    }
}
