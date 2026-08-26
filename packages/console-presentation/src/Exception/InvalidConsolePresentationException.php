<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsolePresentation\Diagnostic\ConsolePresentationDiagnostic;

/** Reports validation or rendering failure without retaining rejected material. */
#[Api(ApiStatus::Experimental)]
final class InvalidConsolePresentationException extends ConsolePresentationException
{
    public static function invalidValue(): self
    {
        return new self(ConsolePresentationDiagnostic::InvalidValue);
    }

    public static function unsupportedResultData(): self
    {
        return new self(ConsolePresentationDiagnostic::UnsupportedResultData);
    }

    public static function renderingFailed(): self
    {
        return new self(ConsolePresentationDiagnostic::RenderingFailed);
    }

    public static function invalidHelp(): self
    {
        return new self(ConsolePresentationDiagnostic::InvalidHelp);
    }
}
