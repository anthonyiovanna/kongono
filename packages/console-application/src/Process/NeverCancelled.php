<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandCancellation;

/** Supplies explicit non-cancellation evidence for one-shot execution. */
#[Api(ApiStatus::Experimental)]
final readonly class NeverCancelled implements CommandCancellation
{
    public function isRequested(): bool
    {
        return false;
    }
}
