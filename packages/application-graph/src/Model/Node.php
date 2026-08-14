<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Holds exactly one graph-local key and one open kind label.
 *
 * Identity within a snapshot is the exact value of the key rather than PHP
 * object identity. The node has no payload, metadata, provenance, or behavior.
 * Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
final readonly class Node
{
    public function __construct(
        private NodeKey $key,
        private NodeKind $kind,
    ) {}

    public function key(): NodeKey
    {
        return $this->key;
    }

    public function kind(): NodeKind
    {
        return $this->kind;
    }
}
