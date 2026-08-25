<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes one immutable accepted SQL-plan shape for deterministic inspection. */
#[Api(ApiStatus::Experimental)]
interface SqlPlan
{
    public function kind(): SqlPlanKind;

    public function requiredCapabilities(): SqlDialectCapabilities;
}
