<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes one immutable typed Graph field and its disclosure boundaries. */
#[Api(ApiStatus::Experimental)]
final readonly class FieldSchema
{
    public function __construct(
        private FieldName $name,
        private FieldType $type,
        private bool $required,
        private FieldDisclosure $disclosure,
        private FactVisibility $visibility,
    ) {}

    public function name(): FieldName
    {
        return $this->name;
    }

    public function type(): FieldType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function disclosure(): FieldDisclosure
    {
        return $this->disclosure;
    }

    public function visibility(): FactVisibility
    {
        return $this->visibility;
    }
}
