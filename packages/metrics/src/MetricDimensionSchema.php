<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricSchemaException;

/** Canonicalizes a bounded dimension schema and proves its maximum series capacity. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricDimensionSchema
{
    /** @var list<MetricDimension> */
    private array $dimensions;

    private int $seriesCapacity;

    /** @param array<array-key, mixed> $dimensions */
    public function __construct(array $dimensions)
    {
        if (!array_is_list($dimensions) || count($dimensions) > 8) {
            throw InvalidMetricSchemaException::dimension();
        }

        $canonical = [];
        $seen = [];
        $capacity = 1;
        foreach ($dimensions as $dimension) {
            if (!$dimension instanceof MetricDimension) {
                throw InvalidMetricSchemaException::dimension();
            }

            $key = $dimension->key()->value();
            if (isset($seen[$key])) {
                throw InvalidMetricSchemaException::dimension();
            }

            $seen[$key] = true;
            $capacity *= count($dimension->allowedValues());
            if ($capacity > 64) {
                throw InvalidMetricSchemaException::dimension();
            }

            $canonical[] = $dimension;
        }

        usort($canonical, static fn(MetricDimension $left, MetricDimension $right): int => strcmp($left->key()->value(), $right->key()->value()));
        $this->dimensions = $canonical;
        $this->seriesCapacity = $capacity;
    }

    /** @return list<MetricDimension> */
    public function dimensions(): array
    {
        return $this->dimensions;
    }

    public function seriesCapacity(): int
    {
        return $this->seriesCapacity;
    }
}
