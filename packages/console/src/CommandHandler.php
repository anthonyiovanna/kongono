<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Executes neutral behavior through one explicit operation and returns one neutral result. */
#[Api(ApiStatus::Experimental)]
interface CommandHandler
{
    public function execute(CommandOperation $operation): CommandResult;
}
