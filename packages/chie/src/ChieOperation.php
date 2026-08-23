<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the two closed Phase 4D read-only operations. */
#[Api(ApiStatus::Experimental)]
enum ChieOperation: string
{
    case Explain = 'explain';
    case Diagnose = 'diagnose';
}
