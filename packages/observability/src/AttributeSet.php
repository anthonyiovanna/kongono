<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Exception\InvalidObservabilityValueException;

/** Stores a bounded unique canonical list of immutable signal attributes. */
#[Api(ApiStatus::Experimental)]
final readonly class AttributeSet
{
    /** @var list<SignalAttribute> */
    private array $attributes;

    /** @param array<array-key, mixed> $attributes */
    public function __construct(array $attributes)
    {
        if (!array_is_list($attributes) || count($attributes) > 32) {
            throw InvalidObservabilityValueException::attribute();
        }

        $canonical = [];
        $seen = [];
        foreach ($attributes as $attribute) {
            if (!$attribute instanceof SignalAttribute) {
                throw InvalidObservabilityValueException::attribute();
            }

            $key = $attribute->key()->value();
            if (isset($seen[$key])) {
                throw InvalidObservabilityValueException::attribute();
            }

            $seen[$key] = true;
            $canonical[] = $attribute;
        }

        usort($canonical, static fn(SignalAttribute $left, SignalAttribute $right): int => strcmp($left->key()->value(), $right->key()->value()));
        $this->attributes = $canonical;
    }

    /** @return list<SignalAttribute> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function find(AttributeKey $key): ?AttributeValue
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->key()->equals($key)) {
                return $attribute->value();
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->attributes);
    }

    public function isEmpty(): bool
    {
        return [] === $this->attributes;
    }
}
