<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\Internal\ObservabilityValidation;

/** Stores one exact canonical signal attribute key. */
#[Api(ApiStatus::Experimental)]
final readonly class AttributeKey
{
    public function __construct(private string $value)
    {
        if (!ObservabilityValidation::isAttributeKey($value)) {
            throw InvalidObservabilityValueException::attribute();
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
