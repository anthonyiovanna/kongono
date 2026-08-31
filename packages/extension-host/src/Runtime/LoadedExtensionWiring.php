<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Runtime\GeneratedExtensionApplicationBoundary;
use Kongono\ExtensionComposition\Runtime\GeneratedExtensionOperationBoundary;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;

/** Holds one validated generated class pair and its one-use hosted startup edge. */
#[Api(ApiStatus::Experimental)]
final class LoadedExtensionWiring
{
    private bool $started = false;

    /**
     * @internal Constructed only by ExtensionHostLoader after exact one-file loading.
     * @param class-string<GeneratedExtensionApplicationBoundary> $applicationClass
     * @param class-string<GeneratedExtensionOperationBoundary> $operationClass
     */
    public function __construct(
        private readonly string $wiringSha256,
        private readonly string $applicationClass,
        private readonly string $operationClass,
    ) {}

    private function __clone(): void {}

    public function wiringSha256(): string
    {
        return $this->wiringSha256;
    }

    /** @return class-string<GeneratedExtensionApplicationBoundary> */
    public function applicationClass(): string
    {
        return $this->applicationClass;
    }

    /** @return class-string<GeneratedExtensionOperationBoundary> */
    public function operationClass(): string
    {
        return $this->operationClass;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    /** @internal Invoked only by TrustedExtensionHost startup. */
    public function start(object ...$applicationProviders): GeneratedExtensionApplicationBoundary
    {
        if ($this->started) {
            throw InvalidGeneratedExtensionHostException::invalid('wiring_already_started');
        }
        $this->started = true;
        $applicationClass = $this->applicationClass;
        try {
            $boundary = $applicationClass::startHosted(array_values($applicationProviders));
        } catch (\InvalidArgumentException $failure) {
            $violation = str_contains($failure->getMessage(), 'count') ? 'provider_count' : 'provider_type';
            throw InvalidGeneratedExtensionHostException::invalid($violation, $failure);
        }
        return $boundary;
    }
}
