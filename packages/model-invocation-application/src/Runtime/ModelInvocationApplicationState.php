<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the exact finite single-operation application lifecycle. */
#[Api(ApiStatus::Experimental)]
enum ModelInvocationApplicationState: string
{
    case Ready = 'ready';
    case Active = 'active';
    case Retired = 'retired';
}
