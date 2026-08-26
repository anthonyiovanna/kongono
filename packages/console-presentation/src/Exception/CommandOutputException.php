<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsolePresentation\Diagnostic\ConsolePresentationDiagnostic;
use Throwable;

/** Retains one output-port failure behind fixed disclosure-safe presentation. */
#[Api(ApiStatus::Public)]
final class CommandOutputException extends ConsolePresentationException
{
    public static function fromPrevious(Throwable $previous): self
    {
        return new self(ConsolePresentationDiagnostic::OutputFailed, $previous);
    }
}
