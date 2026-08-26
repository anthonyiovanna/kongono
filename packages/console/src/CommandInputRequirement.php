<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares whether one named command input must be supplied. */
#[Api(ApiStatus::Experimental)]
enum CommandInputRequirement: string
{
    case Optional = 'optional';
    case Required = 'required';
}
