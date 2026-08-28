<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries the explicit record-or-drop decision for one trace operation. */
#[Api(ApiStatus::Experimental)]
enum TraceSamplingDecision: string
{
    case Record = 'record';
    case Drop = 'drop';
}
