<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes the inspectable kind and derived risk of one version-1 schema operation. */
#[Api(ApiStatus::Experimental)]
interface MigrationOperation
{
    public function kind(): string;

    public function risk(): MigrationRisk;
}
