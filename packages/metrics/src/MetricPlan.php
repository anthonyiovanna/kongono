<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricSchemaException;

/** Owns an exact bounded canonical instrument set for one collection. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricPlan
{
    /** @var list<MetricInstrument> */
    private array $instruments;

    private int $seriesCapacity;

    /** @param array<array-key, mixed> $instruments */
    public function __construct(array $instruments)
    {
        if (!array_is_list($instruments) || count($instruments) < 1 || count($instruments) > 16) {
            throw InvalidMetricSchemaException::plan();
        }

        $canonical = [];
        $seen = [];
        $capacity = 0;
        foreach ($instruments as $instrument) {
            if (!$instrument instanceof MetricInstrument) {
                throw InvalidMetricSchemaException::plan();
            }

            $identity = $instrument->identity();
            $key = $identity->name() . "\0" . $identity->schemaVersion();
            if (isset($seen[$key])) {
                throw InvalidMetricSchemaException::plan();
            }

            $seen[$key] = true;
            $capacity += $instrument->dimensions()->seriesCapacity();
            if ($capacity > 256) {
                throw InvalidMetricSchemaException::plan();
            }

            $canonical[] = $instrument;
        }

        usort($canonical, static function (MetricInstrument $left, MetricInstrument $right): int {
            $name = strcmp($left->identity()->name(), $right->identity()->name());

            return 0 !== $name ? $name : $left->identity()->schemaVersion() <=> $right->identity()->schemaVersion();
        });
        $this->instruments = $canonical;
        $this->seriesCapacity = $capacity;
    }

    /** @return list<MetricInstrument> */
    public function instruments(): array
    {
        return $this->instruments;
    }

    public function seriesCapacity(): int
    {
        return $this->seriesCapacity;
    }
}
