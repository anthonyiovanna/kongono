<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Summary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheApplication\Workflow\PlannedCacheApplicationBuild;

/** Projects one closed Safe cache-profile workflow summary. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheApplicationSummary
{
    private function __construct(private string $setIdentity, private string $buildIdentity, private string $artifactIdentity, private int $profileCount, private bool $current, private bool $publicationRequired, private ?string $executionIntent) {}
    public static function fromPlan(PlannedCacheApplicationBuild $plan, ?string $intent = null): self
    {
        return new self($plan->setIdentity(), $plan->buildIdentity(), $plan->artifactIdentity(), $plan->profileCount(), !$plan->publicationRequired(), $plan->publicationRequired(), $intent);
    }
    public static function fromCurrent(CurrentCacheApplicationBuild $current, ?string $intent = null): self
    {
        $a = $current->artifact();
        return new self($current->profiles()->identity()->value(), $a->buildIdentity()->value(), $a->artifactIdentity()->value(), $current->profiles()->profileCount(), true, false, $intent);
    }
    public function schema(): string
    {
        return 'kongono/cache-application-summary/1';
    }
    public function version(): int
    {
        return 1;
    }
    public function setIdentity(): string
    {
        return $this->setIdentity;
    }
    public function buildIdentity(): string
    {
        return $this->buildIdentity;
    }
    public function artifactIdentity(): string
    {
        return $this->artifactIdentity;
    }
    public function profileCount(): int
    {
        return $this->profileCount;
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
