<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Combines one result field's identity, label, scalar value, and disclosure evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResultField
{
    public function __construct(
        private CommandResultFieldName $name,
        private CommandResultFieldLabel $label,
        private CommandResultValue $value,
        private CommandResultFieldDisclosure $disclosure,
    ) {}

    public function name(): CommandResultFieldName
    {
        return $this->name;
    }

    public function label(): CommandResultFieldLabel
    {
        return $this->label;
    }

    public function value(): CommandResultValue
    {
        return $this->value;
    }

    public function disclosure(): CommandResultFieldDisclosure
    {
        return $this->disclosure;
    }
}
