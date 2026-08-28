<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\Internal\ObservabilityValidation;

/** Retains one exact tagged native scalar for bounded structured attributes. */
#[Api(ApiStatus::Experimental)]
final readonly class AttributeValue
{
    private function __construct(private AttributeValueKind $kind, private bool|int|float|string $value) {}

    public static function boolean(bool $value): self
    {
        return new self(AttributeValueKind::Boolean, $value);
    }

    public static function integer(int $value): self
    {
        return new self(AttributeValueKind::Integer, $value);
    }

    public static function floatingPoint(float $value): self
    {
        if (!is_finite($value)) {
            throw InvalidObservabilityValueException::attribute();
        }

        return new self(AttributeValueKind::FloatingPoint, $value);
    }

    public static function text(string $value): self
    {
        if (!ObservabilityValidation::isBoundedText($value, 1024)) {
            throw InvalidObservabilityValueException::attribute();
        }

        return new self(AttributeValueKind::Text, $value);
    }

    public function kind(): AttributeValueKind
    {
        return $this->kind;
    }

    public function value(): bool|int|float|string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind && $this->value === $other->value;
    }
}
