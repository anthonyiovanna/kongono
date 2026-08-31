<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ExtensionAdmission\Admission\AdmittedExtension;
use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;

/** Produces the exact ambient-free canonical identity document for admitted IR. */
#[Api(ApiStatus::Internal)]
final class CanonicalAdmission
{
    /**
     * @param list<AdmittedExtension> $extensions
     * @param list<ContributionConflictResolution> $resolutions
     * @param list<string> $winningContributions
     * @param list<GraphNodeRecord> $nodes
     * @param list<GraphRelationRecord> $relations
     */
    public static function identity(
        DeclarationSetIdentity $declarationSetIdentity,
        array $extensions,
        array $resolutions,
        array $winningContributions,
        array $nodes,
        array $relations,
    ): AdmissionIdentity {
        $document = [
            'schema' => 'kongono.extension-admission',
            'version' => 1,
            'declaration_set_identity' => $declarationSetIdentity->value(),
            'selections' => array_map(static fn(AdmittedExtension $extension): array => [
                'identity' => $extension->identity()->value(),
                'version' => $extension->version()->value(),
            ], $extensions),
            'applied_resolutions' => array_map(static fn(ContributionConflictResolution $resolution): array => [
                'contribution_identity' => $resolution->contributionIdentity()->value(),
                'winning_extension_identity' => $resolution->winningExtensionIdentity()->value(),
            ], $resolutions),
            'winning_contributions' => $winningContributions,
            'node_identities' => array_map(static fn(GraphNodeRecord $record): string => $record->identity()->value(), $nodes),
            'relation_identities' => array_map(static fn(GraphRelationRecord $record): string => $record->identity()->value(), $relations),
        ];

        try {
            $bytes = json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidExtensionAdmissionException::invalid('canonical_json_failed', 'identity', previous: $failure);
        }

        return new AdmissionIdentity(hash('sha256', $bytes));
    }
}
