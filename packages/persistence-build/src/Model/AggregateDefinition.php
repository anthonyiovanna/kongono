<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one explicit aggregate root and ordered membership. */
#[Api(ApiStatus::Experimental)]
final readonly class AggregateDefinition
{
    /** @var list<string> */
    private array $members;

    /** @param array<array-key, mixed> $members */
    public function __construct(private string $name, private string $root, array $members)
    {
        if (!$this->validIdentifier($name) || !$this->validPhpName($root) || !array_is_list($members) || [] === $members || count($members) > PersistenceModelCompiler::MAX_MEMBERS) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $accepted = [];
        $seen = [];
        foreach ($members as $member) {
            if (!is_string($member) || !$this->validPhpName($member) || isset($seen[$member])) {
                throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
            }
            $seen[$member] = true;
            $accepted[] = $member;
        }
        if (!in_array($root, $accepted, true)) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $this->members = $accepted;
    }

    public function name(): string
    {
        return $this->name;
    }
    public function root(): string
    {
        return $this->root;
    }
    /** @return list<string> */
    public function members(): array
    {
        return $this->members;
    }

    private function validIdentifier(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
            && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function validPhpName(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_FQCN_BYTES
            && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*)(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value);
    }
}
