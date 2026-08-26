<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleRuntime\Diagnostic\ConsoleRuntimeDiagnostic;

/** Reports a bounded Runtime value or lifecycle violation without retaining rejected material. */
#[Api(ApiStatus::Experimental)]
final class InvalidCommandRuntimeValueException extends ConsoleRuntimeException
{
    private static function invalid(): self
    {
        return new self(ConsoleRuntimeDiagnostic::InvalidBinding, false, 0);
    }

    public static function binding(): self
    {
        return self::invalid();
    }

    public static function handlerSet(): self
    {
        return self::invalid();
    }

    public static function operationLifecycle(): self
    {
        return self::invalid();
    }

    public static function reentrantDispatch(): self
    {
        return self::invalid();
    }
}
