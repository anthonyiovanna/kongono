<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries explicit operation correlation without ambient or trace state. */
#[Api(ApiStatus::Experimental)]
final readonly class ObservabilityContext
{
    private function __construct(private ?CorrelationId $correlationId) {}

    public static function uncorrelated(): self
    {
        return new self(null);
    }

    public static function correlated(CorrelationId $correlationId): self
    {
        return new self($correlationId);
    }

    public function correlationId(): ?CorrelationId
    {
        return $this->correlationId;
    }

    public function isCorrelated(): bool
    {
        return null !== $this->correlationId;
    }
}
