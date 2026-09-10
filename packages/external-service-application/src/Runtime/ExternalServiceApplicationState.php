<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the exact one-use lifecycle of an External Service Application. */
#[Api(ApiStatus::Experimental)]
enum ExternalServiceApplicationState: string
{
    case Ready = 'ready';
    case Active = 'active';
    case Retired = 'retired';
}
