<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records declarative relationship cardinality. */
#[Api(ApiStatus::Experimental)]
enum RelationshipCardinality: string
{
    case ToOne = 'to_one';
    case ToMany = 'to_many';
}
