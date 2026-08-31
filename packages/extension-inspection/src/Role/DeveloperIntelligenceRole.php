<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Role;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects an evidence audience without granting authority. */
#[Api(ApiStatus::Experimental)]
enum DeveloperIntelligenceRole: string
{
    case Chie = 'chie';
    case Hantei = 'hantei';
    case Oshie = 'oshie';
}
