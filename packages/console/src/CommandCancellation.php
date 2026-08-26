<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes caller-supplied cancellation evidence without reading global signals. */
#[Api(ApiStatus::Experimental)]
interface CommandCancellation
{
    public function isRequested(): bool;
}
