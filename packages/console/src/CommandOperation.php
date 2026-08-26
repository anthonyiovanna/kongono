<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the explicit lifecycle boundary supplied to one command handler. */
#[Api(ApiStatus::Experimental)]
interface CommandOperation
{
    public function invocation(): CommandInvocation;

    public function cancellation(): CommandCancellation;

    public function state(): CommandLifecycleState;

    public function defer(CommandCleanup $cleanup): void;

    public function retire(): void;
}
