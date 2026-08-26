<?php

declare(strict_types=1);

namespace Kongono\Console\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Diagnostic\ConsoleDiagnostic;

/** Reports a bounded Console contract violation without retaining rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidConsoleValueException extends ConsoleException
{
    private static function fromDiagnostic(ConsoleDiagnostic $diagnostic): self
    {
        return new self($diagnostic);
    }

    public static function identifier(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Validation);
    }

    public static function summary(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Validation);
    }

    public static function definition(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Definition);
    }

    public static function input(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Validation);
    }

    public static function invocation(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Invocation);
    }

    public static function failure(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Failure);
    }

    public static function result(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Failure);
    }

    public static function lifecycle(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Lifecycle);
    }

    public static function specification(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Definition);
    }

    public static function parseResult(): self
    {
        return self::fromDiagnostic(ConsoleDiagnostic::Failure);
    }
}
