<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Distinguishes aggregate-contained declarations from external references. */
#[Api(ApiStatus::Experimental)]
enum RelationshipOwnership: string
{
    case Reference = 'reference';
    case Aggregate = 'aggregate';
}
