<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsolePresentation\Exception\CommandOutputException;
use Throwable;

/** Delivers one completed presentation through exactly one explicit output port. */
#[Api(ApiStatus::Experimental)]
final class CommandPresentationAdapter
{
    public function deliver(
        CommandPresentation $presentation,
        CommandOutputPort $stdout,
        CommandOutputPort $stderr,
    ): int {
        try {
            if ('' !== $presentation->stdout()) {
                $stdout->write($presentation->stdout());
            } else {
                $stderr->write($presentation->stderr());
            }
        } catch (Throwable $failure) {
            throw CommandOutputException::fromPrevious($failure);
        }

        return $presentation->exitStatus()->value;
    }
}
