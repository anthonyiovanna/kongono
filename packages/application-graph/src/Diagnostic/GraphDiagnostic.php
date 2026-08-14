<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

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

    public function contextSchema(): DiagnosticContextSchema
    {
        return match ($this) {
            self::EmptyNodeKey => new DiagnosticContextSchema(
                'Create node key',
                'Node-key value must be non-empty',
                [
                    new DiagnosticFieldSchema(
                        'actual_length',
                        'Actual length',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::EmptyNodeKind => new DiagnosticContextSchema(
                'Create node kind',
                'Node-kind value must be non-empty',
                [
                    new DiagnosticFieldSchema(
                        'actual_length',
                        'Actual length',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::EmptyRelationKind => new DiagnosticContextSchema(
                'Create relation kind',
                'Relation-kind value must be non-empty',
                [
                    new DiagnosticFieldSchema(
                        'actual_length',
                        'Actual length',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::NodesNotList => new DiagnosticContextSchema(
                'Construct Application Graph',
                'Nodes input must be a PHP list',
                [
                    new DiagnosticFieldSchema(
                        'entry_count',
                        'Entry count',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'first_unexpected_key',
                        'First unexpected key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                        false,
                    ),
                ],
            ),
            self::RelationsNotList => new DiagnosticContextSchema(
                'Construct Application Graph',
                'Relations input must be a PHP list',
                [
                    new DiagnosticFieldSchema(
                        'entry_count',
                        'Entry count',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'first_unexpected_key',
                        'First unexpected key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                        false,
                    ),
                ],
            ),
            self::InvalidNodeEntry => new DiagnosticContextSchema(
                'Validate node entry',
                'Every nodes-list entry must be a Node',
                [
                    new DiagnosticFieldSchema(
                        'entry_position',
                        'Entry position',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'actual_value_kind',
                        'Actual value kind',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::InvalidRelationEntry => new DiagnosticContextSchema(
                'Validate relation entry',
                'Every relations-list entry must be a Relation',
                [
                    new DiagnosticFieldSchema(
                        'entry_position',
                        'Entry position',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'actual_value_kind',
                        'Actual value kind',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Safe,
                    ),
                ],
            ),
            self::DuplicateNodeKey => new DiagnosticContextSchema(
                'Validate node uniqueness',
                'Node keys must be unique within a snapshot',
                [
                    new DiagnosticFieldSchema(
                        'node_key',
                        'Node key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                    ),
                ],
            ),
            self::MissingRelationSource => new DiagnosticContextSchema(
                'Validate relation source',
                'Relation source must reference an existing node',
                [
                    new DiagnosticFieldSchema(
                        'relation_position',
                        'Relation position',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'source_key',
                        'Source key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                    ),
                ],
            ),
            self::MissingRelationTarget => new DiagnosticContextSchema(
                'Validate relation target',
                'Relation target must reference an existing node',
                [
                    new DiagnosticFieldSchema(
                        'relation_position',
                        'Relation position',
                        DiagnosticValueKind::Integer,
                        DiagnosticDisclosure::Safe,
                    ),
                    new DiagnosticFieldSchema(
                        'target_key',
                        'Target key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                    ),
                ],
            ),
            self::DuplicateRelationTriple => new DiagnosticContextSchema(
                'Validate relation uniqueness',
                'Source/kind/target triples must be unique',
                [
                    new DiagnosticFieldSchema(
                        'source_key',
                        'Source key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                    ),
                    new DiagnosticFieldSchema(
                        'relation_kind',
                        'Relation kind',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                    ),
                    new DiagnosticFieldSchema(
                        'target_key',
                        'Target key',
                        DiagnosticValueKind::Text,
                        DiagnosticDisclosure::Protected,
                    ),
                ],
            ),
        };
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

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
