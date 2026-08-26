<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines one immutable named input without a value or acquisition behavior. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandInputDefinition
{
    public function __construct(
        private CommandInputName $name,
        private CommandInputRequirement $requirement,
        private CommandInputSensitivity $sensitivity,
    ) {}

    public function name(): CommandInputName
    {
        return $this->name;
    }

    public function requirement(): CommandInputRequirement
    {
        return $this->requirement;
    }

    public function sensitivity(): CommandInputSensitivity
    {
        return $this->sensitivity;
    }
}
