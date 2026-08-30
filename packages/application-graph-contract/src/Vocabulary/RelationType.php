<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Vocabulary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the closed version-1 structural Composition relation vocabulary. */
#[Api(ApiStatus::Experimental)]
enum RelationType: string
{
    case DependsOn = 'composition.depends_on';
    case ConsumesInput = 'composition.consumes_input';
    case ImplementsContract = 'composition.implements_contract';
    case ExposesRoot = 'composition.exposes_root';
}
