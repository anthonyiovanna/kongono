<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseExecutor;

/** Adds explicit single-entity mutation and flush to neutral context observation. */
#[Api(ApiStatus::Experimental)]
interface PersistenceUnitOfWork extends PersistenceContext
{
    public function markRemoved(): void;

    public function flush(DatabaseExecutor $database, PersistenceChangeExecutor $executor): FlushResult;
}
