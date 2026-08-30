<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Schema\FactVisibility;
use Kongono\ApplicationGraphContract\Schema\FieldDisclosure;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Schema\FieldType;

/** Represents one schema-admitted immutable Graph field fact. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphField
{
    public function __construct(
        private FieldName $name,
        private FieldType $type,
        private string $value,
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
    public function value(): string
    {
        return $this->value;
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
