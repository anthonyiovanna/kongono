<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Writes one already-presented document without owning the destination. */
#[Api(ApiStatus::Experimental)]
interface CommandOutputPort
{
    public function write(string $bytes): void;
}
