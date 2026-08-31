<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Exception\InvalidExtensionAdmissionException;
use Kongono\ExtensionAdmission\Projection\ExtensionGraphProvenance;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;

/** Validates bundle ownership, schema, and every provenance field without executing behavior. */
#[Api(ApiStatus::Internal)]
final class GraphContributionValidator
{
    public function validate(
        ExtensionDeclaration $extension,
        ContributionDeclaration $declaration,
        ExtensionGraphContribution $bundle,
    ): void {
        if ('kongono.application-graph-ir' !== $declaration->schema()->name() || 1 !== $declaration->schema()->version()) {
            throw InvalidExtensionAdmissionException::invalid('unsupported_schema', 'contribution');
        }
        if (!$bundle->extensionIdentity()->equals($extension->identity())) {
            throw InvalidExtensionAdmissionException::invalid('bundle_extra', 'contribution');
        }
        if (!$bundle->extensionVersion()->equals($extension->version())) {
            throw InvalidExtensionAdmissionException::invalid('bundle_version_mismatch', 'contribution');
        }
        if (!$bundle->contributionIdentity()->equals($declaration->identity())) {
            throw InvalidExtensionAdmissionException::invalid('bundle_contribution_mismatch', 'contribution');
        }

        foreach ($bundle->nodeRecords() as $record) {
            if (!ExtensionGraphProvenance::matches($record->provenance(), $extension->identity(), $extension->version(), $declaration)) {
                throw InvalidExtensionAdmissionException::invalid('provenance_mismatch', 'provenance');
            }
        }
        foreach ($bundle->relationRecords() as $record) {
            if (!ExtensionGraphProvenance::matches($record->provenance(), $extension->identity(), $extension->version(), $declaration)) {
                throw InvalidExtensionAdmissionException::invalid('provenance_mismatch', 'provenance');
            }
        }
    }
}
