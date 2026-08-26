<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects the one explicit build-time entity construction contract. */
#[Api(ApiStatus::Experimental)]
enum ConstructionKind: string
{
    case Constructor = 'constructor';
    case StaticFactory = 'static_factory';
}
