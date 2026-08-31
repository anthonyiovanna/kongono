<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports structural, currentness, provenance, or Graph-admission failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidExtensionAdmissionException extends ExtensionAdmissionException
{
    private const array KINDS = [
        'admission_identity_grammar', 'selection_not_list', 'selection_count', 'invalid_selection_entry',
        'duplicate_selection', 'noncanonical_selection_order', 'resolution_not_list', 'resolution_count',
        'invalid_resolution_entry', 'duplicate_resolution', 'noncanonical_resolution_order', 'node_not_list',
        'node_count', 'invalid_node_entry', 'duplicate_node', 'noncanonical_node_order', 'relation_not_list',
        'relation_count', 'invalid_relation_entry', 'duplicate_relation', 'noncanonical_relation_order',
        'contribution_identity_not_list', 'invalid_contribution_identity', 'duplicate_contribution_identity',
        'noncanonical_contribution_identity_order', 'contribution_not_list', 'contribution_count',
        'invalid_contribution_entry', 'duplicate_contribution_bundle', 'noncanonical_contribution_order',
        'declaration_set_mismatch', 'selection_missing', 'selection_version_mismatch', 'bundle_missing',
        'bundle_extra', 'bundle_version_mismatch', 'bundle_contribution_mismatch', 'unsupported_schema',
        'provenance_mismatch', 'graph_admission_failed', 'canonical_json_failed',
        'declaration_not_list', 'declaration_count', 'invalid_declaration_entry', 'duplicate_declaration',
        'noncanonical_declaration_order', 'invalid_contribution_identity_entry',
    ];

    public static function invalid(string $kind, string $subject, ?int $count = null, ?int $position = null, ?Throwable $previous = null): self
    {
        $context = ['failure_kind' => in_array($kind, self::KINDS, true) ? $kind : 'unsupported_failure'];
        if (null !== $count) {
            $context['entry_count'] = max(0, $count);
        }
        if (null !== $position) {
            $context['entry_position'] = max(0, $position);
        }

        return new self($context, $previous);
    }
}
