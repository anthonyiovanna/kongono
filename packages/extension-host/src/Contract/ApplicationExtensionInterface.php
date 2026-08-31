<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Contract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies explicit application-lifecycle acquisition and release to the trusted host. */
#[Api(ApiStatus::Experimental)]
interface ApplicationExtensionInterface
{
    public function start(): void;

    public function stop(): void;
}
