<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandResult;

/** Maps one neutral command result to the shared bounded exit-status contract. */
#[Api(ApiStatus::Experimental)]
enum CommandExitStatus: int
{
    case Succeeded = 0;
    case InvalidInput = 2;
    case Precondition = 3;
    case Denied = 4;
    case Capability = 5;
    case Infrastructure = 6;
    case Cancelled = 7;

    public static function forResult(CommandResult $result): self
    {
        $failure = $result->failure();
        if (null === $failure) {
            return self::Succeeded;
        }

        return match ($failure->category()) {
            CommandFailureCategory::InvalidInput => self::InvalidInput,
            CommandFailureCategory::Precondition => self::Precondition,
            CommandFailureCategory::Denied => self::Denied,
            CommandFailureCategory::Capability => self::Capability,
            CommandFailureCategory::Infrastructure => self::Infrastructure,
            CommandFailureCategory::Cancelled => self::Cancelled,
        };
    }
}
