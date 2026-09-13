<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;

/** Declares one canonical field in a closed record schema. */
#[Api(ApiStatus::Experimental)]
final readonly class PresentationFieldSchema
{
    private string $name;

    public function __construct(string $name, private PresentationValueSchema $schema, private bool $required)
    {
        $this->name = ContractGuard::fieldName($name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function schema(): PresentationValueSchema
    {
        return $this->schema;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->required === $other->required
            && $this->schema->equals($other->schema);
    }
}
