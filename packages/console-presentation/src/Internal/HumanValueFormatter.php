<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandResultValue;
use Kongono\Console\CommandResultValueKind;

/** Formats one validated result scalar without locale or environment state. */
#[Api(ApiStatus::Internal)]
final class HumanValueFormatter
{
    public static function format(CommandResultValue $value): string
    {
        return match ($value->kind()) {
            CommandResultValueKind::Text => (string) $value->value(),
            CommandResultValueKind::Integer => sprintf('%d', $value->value()),
            CommandResultValueKind::Boolean => true === $value->value() ? 'true' : 'false',
        };
    }

    private function __construct() {}
}
