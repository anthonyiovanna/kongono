<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricValueException;

/** Stores exact fixed histogram boundaries in strictly increasing numeric order. */
#[Api(ApiStatus::Experimental)]
final readonly class HistogramBoundaries
{
    /** @var list<MetricNumber> */
    private array $boundaries;

    /** @param array<array-key, mixed> $boundaries */
    public function __construct(array $boundaries)
    {
        if (!array_is_list($boundaries) || count($boundaries) < 1 || count($boundaries) > 15) {
            throw InvalidMetricValueException::boundaries();
        }

        $validated = [];
        $previous = null;
        foreach ($boundaries as $boundary) {
            if (!$boundary instanceof MetricNumber || (null !== $previous && $boundary->value() <= $previous->value())) {
                throw InvalidMetricValueException::boundaries();
            }

            $validated[] = $boundary;
            $previous = $boundary;
        }

        $this->boundaries = $validated;
    }

    /** @return list<MetricNumber> */
    public function boundaries(): array
    {
        return $this->boundaries;
    }

    public function count(): int
    {
        return count($this->boundaries);
    }
}
