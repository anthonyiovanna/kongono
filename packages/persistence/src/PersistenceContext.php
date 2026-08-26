<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Exposes observation and explicit retirement for one operation-scoped context.
 *
 * The current operation context retains one entity only. After failure or
 * retirement, only state() and idempotent retire() remain accepted.
 */
#[Api(ApiStatus::Experimental)]
interface PersistenceContext
{
    public function state(): ContextState;

    public function identityOf(object $entity): ?EntityIdentity;

    public function entityForIdentity(EntityIdentity $identity): ?object;

    public function entityState(object $entity): ?EntityState;

    public function changes(): ChangeSet;

    public function retire(): void;
}
