<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Summary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Exception\StorageApplicationInvariantException;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageApplication\Workflow\PlannedStorageApplicationBuild;

/** Projects only bounded Safe profile workflow facts. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageApplicationSummary
{
    private function __construct(
        private int $profileCount,
        private bool $current,
        private bool $publicationRequired,
        private ?string $executionIntent,
    ) {
        if ($profileCount < 1 || $profileCount > 256 || ($current === $publicationRequired)
            || (null !== $executionIntent && !in_array($executionIntent, ['plan', 'apply'], true))) {
            throw StorageApplicationInvariantException::failed('summary_relation');
        }
    }

    public static function fromPlan(PlannedStorageApplicationBuild $plan, ?string $intent = null): self
    {
        return new self($plan->profileCount(), !$plan->publicationRequired(), $plan->publicationRequired(), $intent);
    }

    public static function fromCurrent(CurrentStorageApplicationBuild $current, ?string $intent = null): self
    {
        return new self($current->profiles()->profileCount(), true, false, $intent);
    }

    public function schema(): string
    {
        return 'kongono/storage-application-summary/1';
    }

    public function version(): int
    {
        return 1;
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
