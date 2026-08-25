<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact migration direction requested by a plan or execution. */
#[Api(ApiStatus::Experimental)]
enum MigrationDirection: string
{
    case Apply = 'apply';
    case Revert = 'revert';
}
