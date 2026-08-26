<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Reports one validated presentation-neutral command outcome. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResult
{
    private function __construct(
        private CommandOutcome $outcome,
        private ?CommandFailure $failure,
        private ?CommandResultData $data,
    ) {}

    public static function succeeded(?CommandResultData $data = null): self
    {
        return new self(CommandOutcome::Succeeded, null, $data);
    }

    public static function rejected(CommandFailure $failure): self
    {
        if (!in_array($failure->category(), [CommandFailureCategory::InvalidInput, CommandFailureCategory::Precondition, CommandFailureCategory::Denied], true)) {
            throw InvalidConsoleValueException::result();
        }

        return new self(CommandOutcome::Rejected, $failure, null);
    }

    public static function failed(CommandFailure $failure): self
    {
        if (!in_array($failure->category(), [CommandFailureCategory::Capability, CommandFailureCategory::Infrastructure], true)) {
            throw InvalidConsoleValueException::result();
        }

        return new self(CommandOutcome::Failed, $failure, null);
    }

    public static function cancelled(CommandFailure $failure): self
    {
        if (CommandFailureCategory::Cancelled !== $failure->category()) {
            throw InvalidConsoleValueException::result();
        }

        return new self(CommandOutcome::Cancelled, $failure, null);
    }

    public function outcome(): CommandOutcome
    {
        return $this->outcome;
    }

    public function failure(): ?CommandFailure
    {
        return $this->failure;
    }

    public function data(): ?CommandResultData
    {
        return $this->data;
    }
}
