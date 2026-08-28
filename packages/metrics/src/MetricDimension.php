<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricSchemaException;
use Kongono\Metrics\Internal\MetricsValidation;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;

/** Defines one exact bounded dimension domain before any measurement is admitted. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricDimension
{
    /** @var list<AttributeValue> */
    private array $allowedValues;

    /** @param array<array-key, mixed> $allowedValues */
    public function __construct(private AttributeKey $key, array $allowedValues)
    {
        if (!MetricsValidation::isDimensionKeyAllowed($key->value()) || !array_is_list($allowedValues) || count($allowedValues) < 1 || count($allowedValues) > 8) {
            throw InvalidMetricSchemaException::dimension();
        }

        $canonical = [];
        foreach ($allowedValues as $value) {
            if (!$value instanceof AttributeValue) {
                throw InvalidMetricSchemaException::dimension();
            }

            foreach ($canonical as $existing) {
                if ($value->equals($existing)) {
                    throw InvalidMetricSchemaException::dimension();
                }
            }

            $canonical[] = $value;
        }

        usort($canonical, self::compareValues(...));
        $this->allowedValues = $canonical;
    }

    public function key(): AttributeKey
    {
        return $this->key;
    }

    /** @return list<AttributeValue> */
    public function allowedValues(): array
    {
        return $this->allowedValues;
    }

    public function matches(AttributeValue $value): bool
    {
        foreach ($this->allowedValues as $allowed) {
            if ($allowed->equals($value)) {
                return true;
            }
        }

        return false;
    }

    private static function compareValues(AttributeValue $left, AttributeValue $right): int
    {
        $kindOrder = [
            AttributeValueKind::Boolean->value => 0,
            AttributeValueKind::Integer->value => 1,
            AttributeValueKind::FloatingPoint->value => 2,
            AttributeValueKind::Text->value => 3,
        ];
        $kindComparison = $kindOrder[$left->kind()->value] <=> $kindOrder[$right->kind()->value];
        if (0 !== $kindComparison) {
            return $kindComparison;
        }

        return match ($left->kind()) {
            AttributeValueKind::Boolean, AttributeValueKind::Integer, AttributeValueKind::FloatingPoint => $left->value() <=> $right->value(),
            AttributeValueKind::Text => strcmp((string) $left->value(), (string) $right->value()),
        };
    }
}
