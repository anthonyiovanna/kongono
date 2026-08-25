<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Creates one explicitly named index over ordered unique column identifiers. */
#[Api(ApiStatus::Experimental)]
final readonly class CreateIndex implements MigrationOperation
{
    /** @var list<SqlIdentifier> */
    private array $columns;

    /** @param array<array-key, mixed> $columns */
    public function __construct(
        private SqlQualifiedName $index,
        private SqlQualifiedName $table,
        array $columns,
        private bool $unique,
    ) {
        if (!array_is_list($columns) || [] === $columns || count($columns) > 4_096) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $detached = [];
        $seen = [];
        foreach ($columns as $column) {
            if (!$column instanceof SqlIdentifier || isset($seen[$column->value()])) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $seen[$column->value()] = true;
            $detached[] = $column;
        }
        $this->columns = $detached;
    }

    public function index(): SqlQualifiedName
    {
        return $this->index;
    }

    public function table(): SqlQualifiedName
    {
        return $this->table;
    }

    /** @return list<SqlIdentifier> */
    public function columns(): array
    {
        return [...$this->columns];
    }

    public function unique(): bool
    {
        return $this->unique;
    }

    public function kind(): string
    {
        return 'create_index';
    }

    public function risk(): MigrationRisk
    {
        return MigrationRisk::Safe;
    }
}
