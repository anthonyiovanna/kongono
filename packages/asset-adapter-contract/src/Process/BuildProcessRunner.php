<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Runs one explicit bounded argument-vector process command. */
#[Api(ApiStatus::Experimental)]
interface BuildProcessRunner
{
    public function run(BuildProcessCommand $command): BuildProcessResult;
}
