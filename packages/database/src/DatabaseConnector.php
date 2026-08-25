<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Opens fresh operation-owned database sessions from explicit configuration. */
#[Api(ApiStatus::Public)]
interface DatabaseConnector
{
    public function openSession(): DatabaseSession;
}
