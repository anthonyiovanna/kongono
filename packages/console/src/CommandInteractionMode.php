<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records whether declared interaction is disabled or explicitly permitted. */
#[Api(ApiStatus::Experimental)]
enum CommandInteractionMode: string
{
    case Disabled = 'disabled';
    case Permitted = 'permitted';
}
