<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;

/** Carries validator-reconstructed deterministic generated-PHP evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedExternalServiceBindings
{
    /** @var list<ServiceBinding> */
    private array $bindings;

    /**
     * @param list<ServiceBinding> $bindings
     * @internal Constructed only from package-validated canonical bytes.
     */
    public function __construct(
        private string $canonicalBytes,
        private string $generatedPhpSha256,
        private ExternalServiceBindingSourceIdentity $sourceIdentity,
        private string $sourceSha256,
        private string $bindingArtifactSha256,
        array $bindings,
        private string $generatorImplementationSha256,
    ) {
        $this->bindings = [...$bindings];
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function generatedPhpSha256(): string
    {
        return $this->generatedPhpSha256;
    }

    public function sourceIdentity(): ExternalServiceBindingSourceIdentity
    {
        return $this->sourceIdentity;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function bindingArtifactSha256(): string
    {
        return $this->bindingArtifactSha256;
    }

    /** @return list<ServiceBinding> */
    public function bindings(): array
    {
        return [...$this->bindings];
    }

    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }
}
