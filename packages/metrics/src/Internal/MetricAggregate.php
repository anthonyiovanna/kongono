<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\MetricNumber;

/** Defines the closed aggregate projection needed for one metric observation. */
#[Api(ApiStatus::Internal)]
interface MetricAggregate
{
    public function record(MetricNumber $value): void;

    public function measurementCount(): int;

    public function sum(): ?MetricNumber;

    public function last(): ?MetricNumber;

    public function minimum(): ?MetricNumber;

    public function maximum(): ?MetricNumber;

    /** @return list<int> */
    public function bucketCounts(): array;
}
