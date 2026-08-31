<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;

/** Derives one role-addressed aggregate view without accessing external state. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionIntelligenceDeriver
{
    public function derive(ExtensionPlatformInspection $inspection, DeveloperIntelligenceRole $role): ExtensionIntelligenceView
    {
        $categories = [];
        foreach (ExtensionCategory::cases() as $case) {
            $categories[$case->value] = $inspection->categoryCount($case);
        }
        $lifecycles = [];
        foreach (ExtensionLifecycle::cases() as $case) {
            $lifecycles[$case->value] = $inspection->lifecycleCount($case);
        }
        $visibilities = [];
        foreach (ContributionVisibility::cases() as $case) {
            $visibilities[$case->value] = $inspection->contributionVisibilityCount($case);
        }
        return new ExtensionIntelligenceView($role, $inspection->declarationArtifactSha256(), $inspection->declarationSetIdentity(), $inspection->generatedWiringSha256(), $inspection->admissionIdentity(), $inspection->grantPlanIdentity(), $inspection->extensionContractVersion(), $inspection->declarationCount(), $inspection->implementationCount(), $inspection->decisionCount(), $inspection->applicationProviderCount(), $inspection->operationProviderCount(), $categories, $lifecycles, $inspection->capabilityRequestCount(), $inspection->requiredCapabilityRequestCount(), $inspection->optionalCapabilityRequestCount(), $inspection->contributionCount(), $visibilities);
    }
}
