<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines only the explicit external capability to receive one observation. */
#[Api(ApiStatus::Experimental)]
interface ObservationRecorder
{
    public function record(Observation $observation): void;
}
