<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Closed risk classification derived from the operations in one direction. */
#[Api(ApiStatus::Experimental)]
enum MigrationRisk: string
{
    case Safe = 'safe';
    case Destructive = 'destructive';
}
