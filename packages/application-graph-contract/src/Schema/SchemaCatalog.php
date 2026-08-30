<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Provides a fresh immutable catalog for the exact version-1 Composition vocabulary. */
#[Api(ApiStatus::Experimental)]
final readonly class SchemaCatalog
{
    /** @var list<NodeSchema> */
    private array $nodeSchemas;

    /** @var list<RelationSchema> */
    private array $relationSchemas;

    /**
     * @param list<NodeSchema>     $nodeSchemas
     * @param list<RelationSchema> $relationSchemas
     */
    private function __construct(
        private ContractVersion $version,
        array $nodeSchemas,
        array $relationSchemas,
    ) {
        $this->nodeSchemas = $nodeSchemas;
        $this->relationSchemas = $relationSchemas;
    }

    public static function version1(): self
    {
        return new self(
            ContractVersion::Version1,
            [
                new NodeSchema(NodeType::CompositionContract, [
                    self::field('symbol', true, FieldDisclosure::Protected),
                ]),
                new NodeSchema(NodeType::CompositionInput, [
                    self::field('lifetime', true, FieldDisclosure::Safe),
                    self::field('symbol', true, FieldDisclosure::Protected),
                ]),
                new NodeSchema(NodeType::CompositionRoot, [
                    self::field('contract', true, FieldDisclosure::Protected),
                    self::field('kind', true, FieldDisclosure::Safe),
                ]),
                new NodeSchema(NodeType::CompositionService, [
                    self::field('class', true, FieldDisclosure::Protected),
                    self::field('cleanup', false, FieldDisclosure::Protected),
                    self::field('lifetime', true, FieldDisclosure::Safe),
                ]),
            ],
            [
                self::relation(
                    RelationType::ConsumesInput,
                    NodeType::CompositionService,
                    NodeType::CompositionInput,
                ),
                self::relation(
                    RelationType::DependsOn,
                    NodeType::CompositionService,
                    NodeType::CompositionService,
                ),
                self::relation(
                    RelationType::ExposesRoot,
                    NodeType::CompositionRoot,
                    NodeType::CompositionContract,
                ),
                self::relation(
                    RelationType::ImplementsContract,
                    NodeType::CompositionService,
                    NodeType::CompositionContract,
                ),
            ],
        );
    }

    public function version(): ContractVersion
    {
        return $this->version;
    }

    /** @return list<NodeSchema> */
    public function nodeSchemas(): array
    {
        return [...$this->nodeSchemas];
    }

    /** @return list<RelationSchema> */
    public function relationSchemas(): array
    {
        return [...$this->relationSchemas];
    }

    public function nodeSchema(NodeType $type): NodeSchema
    {
        foreach ($this->nodeSchemas as $schema) {
            if ($schema->type() === $type) {
                return $schema;
            }
        }

        throw new \LogicException('The version-1 node catalog is incomplete.');
    }

    public function relationSchema(RelationType $type): RelationSchema
    {
        foreach ($this->relationSchemas as $schema) {
            if ($schema->type() === $type) {
                return $schema;
            }
        }

        throw new \LogicException('The version-1 relation catalog is incomplete.');
    }

    private static function field(
        string $name,
        bool $required,
        FieldDisclosure $disclosure,
    ): FieldSchema {
        return new FieldSchema(
            new FieldName($name),
            FieldType::Text,
            $required,
            $disclosure,
            FactVisibility::Application,
        );
    }

    private static function relation(
        RelationType $type,
        NodeType $source,
        NodeType $target,
    ): RelationSchema {
        return new RelationSchema($type, [$source], [$target], []);
    }
}
