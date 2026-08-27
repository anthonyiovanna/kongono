<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyReference;

/** Stores one immutable canonical policy identity artifact and its exact evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class SecurityPolicyArtifact implements AuthorizationPolicyEvidence
{
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $modelSha256,
        private string $builderImplementationSha256,
        private AuthorizationPolicyReference $reference,
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
    public function modelSha256(): string
    {
        return $this->modelSha256;
    }
    public function builderImplementationSha256(): string
    {
        return $this->builderImplementationSha256;
    }
    public function reference(): AuthorizationPolicyReference
    {
        return $this->reference;
    }
}
