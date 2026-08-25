<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\AddColumn;
use Kongono\DatabaseMigration\CreateIndex;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DropColumn;
use Kongono\DatabaseMigration\DropIndex;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseMigration\MigrationDefinition;
use Kongono\DatabaseMigration\MigrationDirectionPlan;
use Kongono\DatabaseMigration\MigrationOperation;
use Kongono\DatabaseMigration\RenameColumn;
use Kongono\DatabaseMigration\RenameTable;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;

/** Encodes the fixed-key-order canonical migration identity without ambient input. */
#[Api(ApiStatus::Internal)]
final class MigrationCanonicalJson
{
    /** @param list<MigrationDefinition> $definitions */
    public static function catalog(array $definitions): string
    {
        $migrations = [];
        foreach ($definitions as $definition) {
            $migrations[] = [
                'ordinal' => $definition->ordinal(),
                'id' => $definition->id(),
                'checksum' => $definition->sha256(),
            ];
        }

        return self::encode([
            'schema' => 'kongono.migration-catalog',
            'version' => 1,
            'migrations' => $migrations,
        ]);
    }

    public static function definition(int $ordinal, string $id, MigrationDirectionPlan $apply, ?MigrationDirectionPlan $revert): string
    {
        return self::encode([
            'schema' => 'kongono.migration-catalog',
            'version' => 1,
            'migration' => [
                'ordinal' => $ordinal,
                'id' => $id,
                'apply' => self::direction($apply),
                'revert' => null === $revert ? ['reversible' => false] : [
                    'reversible' => true,
                    'plan' => self::direction($revert),
                ],
            ],
        ]);
    }

    /** @return array{risk: string, operations: list<array<string, mixed>>} */
    private static function direction(MigrationDirectionPlan $plan): array
    {
        return [
            'risk' => $plan->risk()->value,
            'operations' => array_map(self::operation(...), $plan->operations()),
        ];
    }

    /** @return array<string, mixed> */
    private static function operation(MigrationOperation $operation): array
    {
        return match (true) {
            $operation instanceof CreateTable => [
                'kind' => $operation->kind(),
                'table' => self::qualified($operation->table()),
                'columns' => array_map(self::column(...), $operation->columns()),
                'primary_key' => array_map(self::identifier(...), $operation->primaryKey()),
            ],
            $operation instanceof DropTable => [
                'kind' => $operation->kind(),
                'table' => self::qualified($operation->table()),
            ],
            $operation instanceof AddColumn => [
                'kind' => $operation->kind(),
                'table' => self::qualified($operation->table()),
                'column' => self::column($operation->column()),
            ],
            $operation instanceof DropColumn => [
                'kind' => $operation->kind(),
                'table' => self::qualified($operation->table()),
                'column' => $operation->column()->value(),
            ],
            $operation instanceof CreateIndex => [
                'kind' => $operation->kind(),
                'index' => self::qualified($operation->index()),
                'table' => self::qualified($operation->table()),
                'columns' => array_map(self::identifier(...), $operation->columns()),
                'unique' => $operation->unique(),
            ],
            $operation instanceof DropIndex => [
                'kind' => $operation->kind(),
                'index' => self::qualified($operation->index()),
            ],
            $operation instanceof RenameTable => [
                'kind' => $operation->kind(),
                'source' => self::qualified($operation->source()),
                'new_name' => $operation->newName()->value(),
            ],
            $operation instanceof RenameColumn => [
                'kind' => $operation->kind(),
                'table' => self::qualified($operation->table()),
                'source' => $operation->source()->value(),
                'new_name' => $operation->newName()->value(),
            ],
            default => throw InvalidMigrationDefinitionException::invalidDefinition(),
        };
    }

    /** @return array{name: string, type: string, nullable: bool} */
    private static function column(SchemaColumn $column): array
    {
        return [
            'name' => $column->name()->value(),
            'type' => $column->type()->value,
            'nullable' => $column->nullable(),
        ];
    }

    /** @return list<string> */
    private static function qualified(SqlQualifiedName $name): array
    {
        return array_map(self::identifier(...), $name->segments());
    }

    private static function identifier(SqlIdentifier $identifier): string
    {
        return $identifier->value();
    }

    /** @param array<string, mixed> $value */
    private static function encode(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
    }

    private function __construct() {}
}
