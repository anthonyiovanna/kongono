<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Releases one explicitly registered command-operation collaborator. */
#[Api(ApiStatus::Experimental)]
interface CommandCleanup
{
    public function release(): void;
}
