<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies whether a structured result field may cross the presentation boundary. */
#[Api(ApiStatus::Experimental)]
enum CommandResultFieldDisclosure: string
{
    case Safe = 'safe';
    case Protected = 'protected';
}
