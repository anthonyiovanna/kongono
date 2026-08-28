<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\Internal\ObservabilityValidation;

/** Stores opaque correlation only, without identity or authority semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class CorrelationId
{
    public function __construct(private string $value)
    {
        if ('' === $value || !ObservabilityValidation::isBoundedText($value, 128)) {
            throw InvalidObservabilityValueException::context();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
