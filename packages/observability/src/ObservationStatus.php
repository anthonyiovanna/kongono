<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries the bounded outcome status of a neutral observation. */
#[Api(ApiStatus::Experimental)]
enum ObservationStatus: string
{
    case Unset = 'unset';
    case Ok = 'ok';
    case Error = 'error';
}
