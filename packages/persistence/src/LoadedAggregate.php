<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;

/** Holds explicit bounded root and relationship evidence without domain mutation. */
#[Api(ApiStatus::Experimental)]
final readonly class LoadedAggregate
{
    /** @var array<string, object|null|list<object>> */
    private array $relationships;

    /** @param array<array-key, mixed> $relationships */
    public function __construct(private object $root, array $relationships)
    {
        if (array_is_list($relationships) && [] !== $relationships || count($relationships) > AggregateLoadPlan::MAX_RELATIONSHIPS_PER_LOAD_PLAN) {
            $this->invalid();
        }
        $accepted = [];
        $total = 1;
        foreach ($relationships as $name => $value) {
            if (!is_string($name) || '' === $name || strlen($name) > 191 || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name)) {
                $this->invalid();
            }
            if (is_object($value) || null === $value) {
                $accepted[$name] = $value;
                if (is_object($value)) {
                    ++$total;
                }
                continue;
            }
            if (!is_array($value) || !array_is_list($value) || count($value) > AggregateLoadPlan::MAX_RELATED_OBJECTS_PER_TO_MANY) {
                $this->invalid();
            }
            foreach ($value as $related) {
                if (!is_object($related)) {
                    $this->invalid();
                }
                ++$total;
            }
            $accepted[$name] = $value;
        }
        if ($total > AggregateLoadPlan::MAX_TOTAL_OBJECTS) {
            $this->invalid();
        }
        $this->relationships = $accepted;
    }

    public function root(): object
    {
        return $this->root;
    }

    /** @return array<string, object|null|list<object>> */
    public function relationships(): array
    {
        return $this->relationships;
    }

    /** @return object|null|list<object> */
    public function relationship(string $name): object|array|null
    {
        return $this->relationships[$name] ?? null;
    }

    private function invalid(): never
    {
        throw InvalidAggregatePersistenceException::invalid();
    }
}
