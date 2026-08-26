<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares whether a command may receive explicitly permitted interaction. */
#[Api(ApiStatus::Experimental)]
enum CommandInteractionPolicy: string
{
    case None = 'none';
    case Declared = 'declared';
}
