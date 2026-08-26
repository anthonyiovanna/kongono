<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed relationship shapes accepted by aggregate evidence. */
#[Api(ApiStatus::Experimental)]
enum RelationshipCardinality: string
{
    case ToOne = 'to_one';
    case ToMany = 'to_many';
}
