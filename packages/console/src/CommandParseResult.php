<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries exactly one accepted invocation or one neutral parse rejection. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandParseResult
{
    private function __construct(
        private ?CommandInvocation $invocation,
        private ?CommandFailure $failure,
    ) {
        if ((null === $invocation) === (null === $failure)) {
            throw Exception\InvalidConsoleValueException::parseResult();
        }
    }

    public static function accepted(CommandInvocation $invocation): self
    {
        return new self($invocation, null);
    }

    public static function rejected(CommandFailure $failure): self
    {
        return new self(null, $failure);
    }

    public function isAccepted(): bool
    {
        return null !== $this->invocation;
    }

    public function invocation(): ?CommandInvocation
    {
        return $this->invocation;
    }

    public function failure(): ?CommandFailure
    {
        return $this->failure;
    }
}
