<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Throwable;

/** Reports one bounded application-edge invariant failure without rejected material. */
#[Api(ApiStatus::Experimental)]
final class InvalidConsoleApplicationValueException extends ConsoleApplicationException
{
    public static function invalidProcessRequest(): self
    {
        return new self(ConsoleApplicationDiagnostic::InvalidProcessRequest);
    }

    public static function invalidCommandFamily(): self
    {
        return new self(ConsoleApplicationDiagnostic::InvalidProcessRequest);
    }

    public static function applicationFailure(?Throwable $previous = null): self
    {
        return new self(ConsoleApplicationDiagnostic::ApplicationFailure, $previous);
    }
}
