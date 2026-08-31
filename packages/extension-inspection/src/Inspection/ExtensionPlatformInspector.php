<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifact;
use Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionInspection\Exception\ExtensionInspectionLoadException;
use Kongono\ExtensionInspection\Exception\InvalidExtensionInspectionValueException;
use Kongono\ExtensionInspection\Internal\CanonicalJson;
use Throwable;

/** Projects bounded aggregates from already structurally validated detached bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionPlatformInspector
{
    public function inspect(ExtensionDeclarationArtifact $declaration, GeneratedExtensionWiringArtifact $wiring): ExtensionPlatformInspection
    {
        if (!$declaration->declarationSetIdentity()->equals($wiring->declarationSetIdentity())) {
            throw ExtensionInspectionLoadException::identityMismatch();
        }
        try {
            $root = CanonicalJson::decodeObject($declaration->canonicalBytes());
            $declarations = $root['declarations'] ?? null;
            if (!is_array($declarations) || !array_is_list($declarations) || count($declarations) !== $declaration->declarationCount()) {
                throw InvalidExtensionInspectionValueException::aggregate('count');
            }
            $categories = array_fill_keys(array_map(static fn(ExtensionCategory $case): string => $case->value, ExtensionCategory::cases()), 0);
            $lifecycles = array_fill_keys(array_map(static fn(ExtensionLifecycle $case): string => $case->value, ExtensionLifecycle::cases()), 0);
            $visibilities = array_fill_keys(array_map(static fn(ContributionVisibility $case): string => $case->value, ContributionVisibility::cases()), 0);
            $required = 0;
            $optional = 0;
            $contributionCount = 0;
            foreach ($declarations as $raw) {
                if (!is_array($raw) || array_is_list($raw)) {
                    throw InvalidExtensionInspectionValueException::aggregate('serialization');
                }
                $category = $raw['category'] ?? null;
                $lifecycle = $raw['lifecycle'] ?? null;
                if (!is_string($category) || !isset($categories[$category]) || !is_string($lifecycle) || !isset($lifecycles[$lifecycle])) {
                    throw InvalidExtensionInspectionValueException::aggregate('count');
                }
                ++$categories[$category];
                ++$lifecycles[$lifecycle];
                $requests = $raw['capability_requests'] ?? null;
                $contributions = $raw['contributions'] ?? null;
                if (!is_array($requests) || !array_is_list($requests) || !is_array($contributions) || !array_is_list($contributions)) {
                    throw InvalidExtensionInspectionValueException::aggregate('serialization');
                }
                foreach ($requests as $request) {
                    if (!is_array($request) || array_is_list($request)) {
                        throw InvalidExtensionInspectionValueException::aggregate('serialization');
                    }
                    match ($request['requirement'] ?? null) {
                        CapabilityRequirement::Required->value => ++$required,
                        CapabilityRequirement::Optional->value => ++$optional,
                        default => throw InvalidExtensionInspectionValueException::aggregate('capability_counts'),
                    };
                }
                foreach ($contributions as $contribution) {
                    if (!is_array($contribution) || array_is_list($contribution) || !is_string($contribution['visibility'] ?? null) || !isset($visibilities[$contribution['visibility']])) {
                        throw InvalidExtensionInspectionValueException::aggregate('visibility_counts');
                    }
                    ++$visibilities[$contribution['visibility']];
                    ++$contributionCount;
                }
            }
            $builder = $root['builder'] ?? null;
            if (!is_array($builder) || array_is_list($builder)) {
                throw InvalidExtensionInspectionValueException::aggregate('serialization');
            }
            $rawContractVersion = $builder['contract_version'] ?? null;
            if (!is_int($rawContractVersion)) {
                throw InvalidExtensionInspectionValueException::aggregate('serialization');
            }
            $contractVersion = ContractVersion::tryFrom($rawContractVersion);
            if (null === $contractVersion) {
                throw InvalidExtensionInspectionValueException::aggregate('serialization');
            }
            return new ExtensionPlatformInspection(
                $declaration->artifactSha256(),
                $declaration->declarationSetIdentity(),
                $wiring->wiringSha256(),
                $wiring->admissionIdentity()->value(),
                $wiring->grantPlanIdentity()->value(),
                $contractVersion,
                $declaration->declarationCount(),
                $wiring->implementationCount(),
                $wiring->decisionCount(),
                $wiring->applicationProviderCount(),
                $wiring->operationProviderCount(),
                $categories,
                $lifecycles,
                $required + $optional,
                $required,
                $optional,
                $contributionCount,
                $visibilities,
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExtensionInspectionLoadException) {
                throw $failure;
            }
            if ($failure instanceof InvalidExtensionInspectionValueException) {
                throw $failure;
            }
            throw InvalidExtensionInspectionValueException::aggregate('serialization', $failure);
        }
    }
}
