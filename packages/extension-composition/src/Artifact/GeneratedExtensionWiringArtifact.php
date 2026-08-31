<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionComposition\Identity\GrantPlanIdentity;

/** Stores detached generated PHP and only its bounded exact identity metadata and counts. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedExtensionWiringArtifact
{
    /** @internal Constructed only from validated package-owned generated state. */
    public function __construct(
        private string $canonicalBytes,
        private string $wiringSha256,
        private DeclarationSetIdentity $declarationSetIdentity,
        private AdmissionIdentity $admissionIdentity,
        private GrantPlanIdentity $grantPlanIdentity,
        private string $generatorImplementationSha256,
        private string $generatedNamespace,
        private string $applicationClass,
        private string $operationClass,
        private int $implementationCount,
        private int $decisionCount,
        private int $applicationProviderCount,
        private int $operationProviderCount,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }
    public function wiringSha256(): string
    {
        return $this->wiringSha256;
    }
    public function declarationSetIdentity(): DeclarationSetIdentity
    {
        return $this->declarationSetIdentity;
    }
    public function admissionIdentity(): AdmissionIdentity
    {
        return $this->admissionIdentity;
    }
    public function grantPlanIdentity(): GrantPlanIdentity
    {
        return $this->grantPlanIdentity;
    }
    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }
    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }
    public function applicationClass(): string
    {
        return $this->applicationClass;
    }
    public function operationClass(): string
    {
        return $this->operationClass;
    }
    public function implementationCount(): int
    {
        return $this->implementationCount;
    }
    public function decisionCount(): int
    {
        return $this->decisionCount;
    }
    public function applicationProviderCount(): int
    {
        return $this->applicationProviderCount;
    }
    public function operationProviderCount(): int
    {
        return $this->operationProviderCount;
    }
}
