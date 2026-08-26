<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one entity field without introducing runtime behavior. */
#[Api(ApiStatus::Experimental)]
enum FieldRole: string
{
    case Identity = 'identity';
    case Value = 'value';
    case Concurrency = 'concurrency';
}
