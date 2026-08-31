<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionInspection\Derivation\ExtensionIntelligenceView;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection;

/** Independently compares every allowed view value without repair or rederivation. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionIntelligenceVerifier
{
    public function verify(ExtensionPlatformInspection $inspection, ExtensionIntelligenceView $view): ExtensionIntelligenceVerification
    {
        $identity = $inspection->declarationArtifactSha256() === $view->declarationArtifactSha256() && $inspection->declarationSetIdentity()->value() === $view->declarationSetIdentity()->value() && $inspection->generatedWiringSha256() === $view->generatedWiringSha256() && $inspection->admissionIdentity() === $view->admissionIdentity() && $inspection->grantPlanIdentity() === $view->grantPlanIdentity() && $this->sameInteger($inspection->extensionContractVersion()->value, $view->extensionContractVersion()->value);
        $declarations = $inspection->declarationCount() === $view->declarationCount() && $inspection->implementationCount() === $view->implementationCount() && $inspection->decisionCount() === $view->decisionCount() && $inspection->applicationProviderCount() === $view->applicationProviderCount() && $inspection->operationProviderCount() === $view->operationProviderCount();
        $categoryLifecycle = true;
        foreach (ExtensionCategory::cases() as $case) {
            $categoryLifecycle = $categoryLifecycle && $inspection->categoryCount($case) === $view->categoryCount($case);
        } foreach (ExtensionLifecycle::cases() as $case) {
            $categoryLifecycle = $categoryLifecycle && $inspection->lifecycleCount($case) === $view->lifecycleCount($case);
        }
        $capabilities = $inspection->capabilityRequestCount() === $view->capabilityRequestCount() && $inspection->requiredCapabilityRequestCount() === $view->requiredCapabilityRequestCount() && $inspection->optionalCapabilityRequestCount() === $view->optionalCapabilityRequestCount();
        $contributions = $inspection->contributionCount() === $view->contributionCount();
        foreach (ContributionVisibility::cases() as $case) {
            $contributions = $contributions && $inspection->contributionVisibilityCount($case) === $view->contributionVisibilityCount($case);
        }
        $visibility = ExtensionPlatformInspection::SCOPE === $view->scope() && ExtensionPlatformInspection::VISIBILITY === $view->visibility() && ExtensionPlatformInspection::CURRENTNESS === $view->currentness();
        $authority = ExtensionPlatformInspection::AUTHORITY === $view->authority() && ExtensionPlatformInspection::HOST_STATE === $view->hostState() && ExtensionPlatformInspection::EXECUTION === $view->execution() && ExtensionPlatformInspection::TRUSTED_CODE_ISOLATION === $view->trustedCodeIsolation();
        $checks = ['identity-binding' => $identity, 'declaration-counts' => $declarations, 'category-lifecycle-counts' => $categoryLifecycle, 'capability-counts' => $capabilities, 'contribution-counts' => $contributions, 'visibility-semantics' => $visibility, 'authority-runtime-semantics' => $authority];
        $failures = [];
        foreach ($checks as $id => $passed) {
            if (!$passed) {
                $failures[] = $id . '-failed';
            }
        }
        return new ExtensionIntelligenceVerification([] === $failures ? ExtensionIntelligenceVerdict::Pass : ExtensionIntelligenceVerdict::Fail, $view->role(), $inspection->declarationArtifactSha256(), $inspection->declarationSetIdentity(), $checks, $failures);
    }

    private function sameInteger(int $expected, int $observed): bool
    {
        return $expected === $observed;
    }
}
