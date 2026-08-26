<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one explicit aggregate cascade operation. */
#[Api(ApiStatus::Experimental)]
enum CascadeAction: string
{
    case Persist = 'persist';
    case Remove = 'remove';
}
