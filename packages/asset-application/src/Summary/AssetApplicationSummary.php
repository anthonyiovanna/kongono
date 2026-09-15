<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Summary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Workflow\AdapterAssetApplicationBuild;
use Kongono\AssetApplication\Workflow\CurrentAssetApplicationBuild;
use Kongono\AssetApplication\Workflow\PlannedAssetApplicationBuild;

/** Exposes only bounded Safe application and command summary evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetApplicationSummary
{
    private const string SCHEMA = 'kongono.asset-application-summary';
    private const int VERSION = 1;

    public function __construct(
        private string $producerKind,
        private string $sourceSetIdentity,
        private string $graphIdentity,
        private string $buildIdentity,
        private string $resolutionIdentity,
        private string $artifactBasename,
        private int $declarationCount,
        private int $dependencyCount,
        private int $entryPointCount,
        private int $outputCount,
        private bool $current,
        private bool $publicationRequired,
        private ?string $executionIntent,
    ) {}

    public static function fromPlan(PlannedAssetApplicationBuild $plan, ?string $intent = null): self
    {
        return new self('native', $plan->sourceSetIdentity(), $plan->graphIdentity(), $plan->buildIdentity(), $plan->resolutionIdentity(), $plan->artifactBasename(), $plan->declarationCount(), $plan->dependencyCount(), $plan->entryPointCount(), $plan->outputCount(), !$plan->publicationRequired(), $plan->publicationRequired(), $intent);
    }

    public static function fromCurrent(CurrentAssetApplicationBuild $current, ?string $intent = null): self
    {
        $artifact = $current->resolutionArtifact();

        return new self('native', $artifact->sourceSetIdentity(), $artifact->graphIdentity(), $artifact->buildIdentity(), $artifact->identity()->value(), $current->artifactBasename(), $current->declarationCount(), $current->dependencyCount(), $current->entryPointCount(), $current->outputCount(), true, false, $intent);
    }

    public static function fromAdapter(AdapterAssetApplicationBuild $current, ?string $intent = null): self
    {
        $artifact = $current->resolutionArtifact();

        return new self('adapter', $artifact->sourceSetIdentity(), $artifact->graphIdentity(), $artifact->buildIdentity(), $artifact->identity()->value(), '', $artifact->entryCount(), $current->dependencyCount(), $current->entryCount(), $current->outputCount(), true, false, $intent);
    }

    public function schema(): string
    {
        return self::SCHEMA;
    }
    public function version(): int
    {
        return self::VERSION;
    }
    public function producerKind(): string
    {
        return $this->producerKind;
    }
    public function sourceSetIdentity(): string
    {
        return $this->sourceSetIdentity;
    }
    public function graphIdentity(): string
    {
        return $this->graphIdentity;
    }
    public function buildIdentity(): string
    {
        return $this->buildIdentity;
    }
    public function resolutionIdentity(): string
    {
        return $this->resolutionIdentity;
    }
    public function artifactBasename(): string
    {
        return $this->artifactBasename;
    }
    public function declarationCount(): int
    {
        return $this->declarationCount;
    }
    public function dependencyCount(): int
    {
        return $this->dependencyCount;
    }
    public function entryPointCount(): int
    {
        return $this->entryPointCount;
    }
    public function outputCount(): int
    {
        return $this->outputCount;
    }
    public function current(): bool
    {
        return $this->current;
    }
    public function publicationRequired(): bool
    {
        return $this->publicationRequired;
    }
    public function executionIntent(): ?string
    {
        return $this->executionIntent;
    }
}
