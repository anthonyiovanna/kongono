<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;

/** Carries only builder- or validator-verified canonical binding evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceBindingArtifact
{
    /** @internal Constructed only from package-verified canonical state. */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private ExternalServiceBindingSource $source,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function sourceIdentity(): ExternalServiceBindingSourceIdentity
    {
        return $this->source->identity();
    }

    /** @return list<ServiceBinding> */
    public function bindings(): array
    {
        return $this->source->bindings();
    }

    public function source(): ExternalServiceBindingSource
    {
        return $this->source;
    }
}
