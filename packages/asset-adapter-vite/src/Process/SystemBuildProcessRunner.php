<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Process;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterContract\Process\BuildProcessResult;
use Kongono\AssetAdapterContract\Process\BuildProcessRunner;
use Kongono\AssetAdapterVite\Internal\ProcessLifecycle;

/** Runs one direct shell-free process with bounded streams and deadline. */
#[Api(ApiStatus::Experimental)]
final class SystemBuildProcessRunner implements BuildProcessRunner
{
    public function run(BuildProcessCommand $command): BuildProcessResult
    {
        return ProcessLifecycle::execute($command);
    }
}
