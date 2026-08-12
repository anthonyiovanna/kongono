<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Holds one directed, typed connection between two graph-local node keys.
 *
 * Structural equality is the exact source-value, kind-value, target-value
 * triple. The relation has no independent ID, payload, weight, or provenance.
 * Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
final readonly class Relation
{
    public function __construct(
        private NodeKey $source,
        private RelationKind $kind,
        private NodeKey $target,
    ) {}

    public function source(): NodeKey
    {
        return $this->source;
    }

    public function kind(): RelationKind
    {
        return $this->kind;
    }

    public function target(): NodeKey
    {
        return $this->target;
    }
}
