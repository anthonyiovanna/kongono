<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Diagnostic;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Owns the fixed metadata for deliberate Application Graph failures.
 *
 * The backed identifier is the stable semantic identity. Titles and remediation
 * remain human diagnostic prose and carry no transport or disclosure policy.
 */
#[Api(ApiStatus::Internal)]
enum GraphDiagnostic: string
{
    case EmptyNodeKey = 'KGN-GRAPH-1001';
    case EmptyNodeKind = 'KGN-GRAPH-1002';
    case EmptyRelationKind = 'KGN-GRAPH-1003';
    case NodesNotList = 'KGN-GRAPH-1004';
    case RelationsNotList = 'KGN-GRAPH-1005';
    case InvalidNodeEntry = 'KGN-GRAPH-1006';
    case InvalidRelationEntry = 'KGN-GRAPH-1007';
    case DuplicateNodeKey = 'KGN-GRAPH-1008';
    case MissingRelationSource = 'KGN-GRAPH-1009';
    case MissingRelationTarget = 'KGN-GRAPH-1010';
    case DuplicateRelationTriple = 'KGN-GRAPH-1011';

    public function title(): string
    {
        return match ($this) {
            self::EmptyNodeKey => 'Empty node-key value',
            self::EmptyNodeKind => 'Empty node-kind value',
            self::EmptyRelationKind => 'Empty relation-kind value',
            self::NodesNotList => 'Nodes input is not a zero-based consecutive list',
            self::RelationsNotList => 'Relations input is not a zero-based consecutive list',
            self::InvalidNodeEntry => 'A nodes-list entry is not a `Node`',
            self::InvalidRelationEntry => 'A relations-list entry is not a `Relation`',
            self::DuplicateNodeKey => 'Duplicate node-key value',
            self::MissingRelationSource => 'Relation source does not reference an existing node',
            self::MissingRelationTarget => 'Relation target does not reference an existing node',
            self::DuplicateRelationTriple => 'Duplicate source/kind/target relation triple',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::EmptyNodeKey => 'Supply a non-empty opaque graph-local key',
            self::EmptyNodeKind => 'Supply a non-empty open node-kind label',
            self::EmptyRelationKind => 'Supply a non-empty open relation-kind label',
            self::NodesNotList => 'Reindex the node array as a PHP list',
            self::RelationsNotList => 'Reindex the relation array as a PHP list',
            self::InvalidNodeEntry => 'Replace the reported entry with a valid `Node`',
            self::InvalidRelationEntry => 'Replace the reported entry with a valid `Relation`',
            self::DuplicateNodeKey => 'Remove or rename the duplicate within that snapshot',
            self::MissingRelationSource => 'Add the source node or correct the source key',
            self::MissingRelationTarget => 'Add the target node or correct the target key',
            self::DuplicateRelationTriple => 'Remove the duplicate or use a different relation kind when semantically distinct',
        };
    }
}
