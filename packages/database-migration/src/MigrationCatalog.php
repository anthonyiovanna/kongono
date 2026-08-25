<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseMigration\Internal\MigrationCanonicalJson;

/** Owns the explicit ordered authoritative version-1 migration source. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationCatalog
{
    /** @var list<MigrationDefinition> */
    private array $definitions;

    private string $canonicalBytes;

    private string $sha256;

    /** @param array<array-key, mixed> $definitions */
    public function __construct(array $definitions)
    {
        if (!array_is_list($definitions) || count($definitions) > 4_096) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $detached = [];
        $seenIds = [];
        $operationCount = 0;
        foreach ($definitions as $index => $definition) {
            if (!$definition instanceof MigrationDefinition || $definition->ordinal() !== $index + 1 || isset($seenIds[$definition->id()])) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $seenIds[$definition->id()] = true;
            $operationCount += count($definition->apply()->operations());
            if (null !== $definition->revert()) {
                $operationCount += count($definition->revert()->operations());
            }
            if ($operationCount > 65_535) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $detached[] = $definition;
        }
        $this->definitions = $detached;
        $this->canonicalBytes = MigrationCanonicalJson::catalog($detached);
        $this->sha256 = hash('sha256', $this->canonicalBytes);
    }

    /** @return list<MigrationDefinition> */
    public function definitions(): array
    {
        return [...$this->definitions];
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
}
