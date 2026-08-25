<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed version-1 neutral scalar and binary value vocabulary. */
#[Api(ApiStatus::Experimental)]
enum DatabaseValueKind
{
    case Null;
    case Boolean;
    case Integer;
    case Float;
    case Text;
    case Binary;
}
