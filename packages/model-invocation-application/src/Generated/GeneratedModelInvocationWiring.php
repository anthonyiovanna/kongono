<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileIdentity;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;

/** Carries validator-reconstructed canonical generated-wiring evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedModelInvocationWiring
{
    /** @internal Constructed only from package-validated canonical bytes. */
    public function __construct(
        private ModelInvocationApplicationProfileIdentity $applicationProfileIdentity,
        private InvocationPlanIdentity $planIdentity,
        private InvocationIdentity $invocationIdentity,
        private ProfileIdentity $modelProfileIdentity,
        private ProviderIdentity $providerIdentity,
        private ModelIdentity $modelIdentity,
        private AdapterIdentity $adapterIdentity,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $requestSha256,
        private string $implementationSha256,
        private string $generatedNamespace,
        private string $generatedClass,
        private string $canonicalBytes,
        private string $generatedWiringSha256,
    ) {}

    public function applicationProfileIdentity(): ModelInvocationApplicationProfileIdentity
    {
        return $this->applicationProfileIdentity;
    }
    public function planIdentity(): InvocationPlanIdentity
    {
        return $this->planIdentity;
    }
    public function invocationIdentity(): InvocationIdentity
    {
        return $this->invocationIdentity;
    }
    public function modelProfileIdentity(): ProfileIdentity
    {
        return $this->modelProfileIdentity;
    }
    public function providerIdentity(): ProviderIdentity
    {
        return $this->providerIdentity;
    }
    public function modelIdentity(): ModelIdentity
    {
        return $this->modelIdentity;
    }
    public function adapterIdentity(): AdapterIdentity
    {
        return $this->adapterIdentity;
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
    public function implementationSha256(): string
    {
        return $this->implementationSha256;
    }
    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }
    public function generatedClass(): string
    {
        return $this->generatedClass;
    }
    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }
    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }
}
