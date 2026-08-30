<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Role;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects a derived audience without granting any operational authority. */
#[Api(ApiStatus::Experimental)]
enum DeveloperIntelligenceRole: string
{
    case Chie = 'chie';
    case Hantei = 'hantei';
    case Oshie = 'oshie';
}
