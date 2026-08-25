<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\SqlIdentifier;

/** Describes one explicitly typed schema column without defaults or provider fragments. */
#[Api(ApiStatus::Experimental)]
final readonly class SchemaColumn
{
    public function __construct(
        private SqlIdentifier $name,
        private SchemaColumnType $type,
        private bool $nullable,
    ) {}

    public function name(): SqlIdentifier
    {
        return $this->name;
    }

    public function type(): SchemaColumnType
    {
        return $this->type;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }
}
