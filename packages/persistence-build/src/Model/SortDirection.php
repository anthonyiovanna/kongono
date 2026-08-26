<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records deterministic declarative ordering direction. */
#[Api(ApiStatus::Experimental)]
enum SortDirection: string
{
    case Ascending = 'ascending';
    case Descending = 'descending';
}
