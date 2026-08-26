<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one named already-normalized input and its sensitivity evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandInput
{
    public function __construct(
        private CommandInputName $name,
        private CommandInputValue $value,
        private CommandInputSensitivity $sensitivity,
    ) {}

    public function name(): CommandInputName
    {
        return $this->name;
    }

    public function value(): CommandInputValue
    {
        return $this->value;
    }

    public function sensitivity(): CommandInputSensitivity
    {
        return $this->sensitivity;
    }
}
