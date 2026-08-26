<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;

/** Stores inert one-level cascade evidence without executing or discovering work. */
#[Api(ApiStatus::Experimental)]
final readonly class AggregateCascadePlan
{
    public const MAX_CASCADE_POLICIES_PER_AGGREGATE = 64;
    public const MAX_CHANGES = 4096;

    /** @var list<RelationshipCascadePolicy> */
    private array $policies;

    /** @param array<array-key, mixed> $policies */
    public function __construct(
        private string $aggregateName,
        private string $rootType,
        array $policies,
        private int $maxChanges,
    ) {
        if (!$this->identifier($aggregateName) || !$this->phpName($rootType) || !array_is_list($policies) || count($policies) > self::MAX_CASCADE_POLICIES_PER_AGGREGATE || $maxChanges < 1 || $maxChanges > self::MAX_CHANGES) {
            $this->invalid();
        }
        $accepted = [];
        $names = [];
        foreach ($policies as $policy) {
            if (!$policy instanceof RelationshipCascadePolicy || isset($names[$policy->relationshipName()])) {
                $this->invalid();
            }
            $names[$policy->relationshipName()] = true;
            $accepted[] = $policy;
        }
        $this->policies = $accepted;
    }

    public function aggregateName(): string
    {
        return $this->aggregateName;
    }

    public function rootType(): string
    {
        return $this->rootType;
    }

    /** @return list<RelationshipCascadePolicy> */
    public function policies(): array
    {
        return $this->policies;
    }

    public function maxChanges(): int
    {
        return $this->maxChanges;
    }

    private function identifier(string $value): bool
    {
        return '' !== $value && strlen($value) <= 191 && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function phpName(string $value): bool
    {
        return '' !== $value && strlen($value) <= 255 && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function invalid(): never
    {
        throw InvalidAggregatePersistenceException::invalid();
    }
}
