<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes one explicit bounded metric collection lifecycle. */
#[Api(ApiStatus::Experimental)]
interface MetricCollection
{
    public function state(): MetricCollectionState;

    public function record(MetricInstrument $instrument, MetricMeasurement $measurement): void;

    public function collect(): void;

    public function retire(): void;

    public function activeSeriesCount(): int;
}
