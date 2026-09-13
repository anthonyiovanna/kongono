<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Summary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Workflow\CurrentViewApplicationBuild;
use Kongono\ViewApplication\Workflow\PlannedViewApplicationBuild;

/** Exposes only bounded Safe command summary evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewApplicationSummary
{
    private const string SCHEMA = 'kongono.view-application-summary';
    private const int VERSION = 1;

    public function __construct(
        private string $sourceSha256,
        private string $compiledSha256,
        private string $modelSha256,
        private string $generatedSha256,
        private string $artifactBasename,
        private int $viewCount,
        private bool $current,
        private bool $publicationRequired,
        private ?string $executionIntent,
    ) {}

    public static function fromPlan(PlannedViewApplicationBuild $plan, ?string $executionIntent = null): self
    {
        return new self($plan->sourceIdentity()->value(), $plan->compiledViewIdentity()->value(), $plan->modelSha256(), $plan->generatedPhpSha256(), $plan->artifactBasename(), $plan->viewCount(), !$plan->publicationRequired(), $plan->publicationRequired(), $executionIntent);
    }

    public static function fromCurrent(CurrentViewApplicationBuild $current, ?string $executionIntent = null): self
    {
        return new self($current->sourceIdentity()->value(), $current->compiledViewIdentity()->value(), $current->modelSha256(), $current->generatedPhpSha256(), $current->artifactBasename(), $current->viewCount(), true, false, $executionIntent);
    }

    public function schema(): string
    {
        return self::SCHEMA;
    }
    public function version(): int
    {
        return self::VERSION;
    }
    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
    public function compiledSha256(): string
    {
        return $this->compiledSha256;
    }
    public function modelSha256(): string
    {
        return $this->modelSha256;
    }
    public function generatedSha256(): string
    {
        return $this->generatedSha256;
    }
    public function artifactBasename(): string
    {
        return $this->artifactBasename;
    }
    public function viewCount(): int
    {
        return $this->viewCount;
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
