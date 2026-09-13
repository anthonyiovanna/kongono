<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies inert render lifecycle vocabulary only. */
#[Api(ApiStatus::Experimental)]
enum RenderOperationState: string
{
    case Requested = 'requested';
    case Rendering = 'rendering';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retired = 'retired';
}
