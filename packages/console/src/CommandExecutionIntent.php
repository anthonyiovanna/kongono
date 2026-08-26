<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Makes observation, mutation planning, and mutation application explicit. */
#[Api(ApiStatus::Experimental)]
enum CommandExecutionIntent: string
{
    case Observe = 'observe';
    case Plan = 'plan';
    case Apply = 'apply';
}
