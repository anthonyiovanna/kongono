<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares whether a command is read-only or may plan and apply mutation. */
#[Api(ApiStatus::Experimental)]
enum CommandMutationPolicy: string
{
    case ReadOnly = 'read_only';
    case MutationCapable = 'mutation_capable';
}
