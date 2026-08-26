<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleCommands\Diagnostic\ConsoleCommandsDiagnostic;

/** Reports a bounded command-family or capability invariant violation. */
#[Api(ApiStatus::Experimental)]
final class InvalidConsoleCommandsValueException extends ConsoleCommandsException
{
    public static function invalidFamily(): self
    {
        return new self(ConsoleCommandsDiagnostic::InvalidFamily);
    }

    public static function invalidCapabilityState(): self
    {
        return new self(ConsoleCommandsDiagnostic::InvalidCapabilityState);
    }
}
