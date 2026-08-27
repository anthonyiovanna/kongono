<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\ResourceReference;

/**
 * Defines one protected operation without carrying or implying permission.
 *
 * @template TResult
 */
#[Api(ApiStatus::Experimental)]
interface ProtectedCapability
{
    public function action(): Action;

    public function resource(): ResourceReference;

    /** @return TResult */
    public function execute(): mixed;
}
