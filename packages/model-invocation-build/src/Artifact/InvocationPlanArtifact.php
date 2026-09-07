<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;

/** Stores only builder- or validator-verified detached invocation-plan evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationPlanArtifact
{
    /** @internal Constructed only from package-verified canonical state. */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $requestSha256,
        private InvocationPlanIdentity $planIdentity,
        private AdapterIdentity $adapterIdentity,
        private InvocationIdentity $invocationIdentity,
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

    public function requestSha256(): string
    {
        return $this->requestSha256;
    }

    public function planIdentity(): InvocationPlanIdentity
    {
        return $this->planIdentity;
    }

    public function adapterIdentity(): AdapterIdentity
    {
        return $this->adapterIdentity;
    }

    public function invocationIdentity(): InvocationIdentity
    {
        return $this->invocationIdentity;
    }
}
