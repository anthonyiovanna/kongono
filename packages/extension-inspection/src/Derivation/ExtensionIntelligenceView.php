<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;

/** Carries one deterministic Safe aggregate view for a selected role. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionIntelligenceView
{
    /** @var array<string, int> */
    private array $categoryCounts;
    /** @var array<string, int> */
    private array $lifecycleCounts;
    /** @var array<string, int> */
    private array $visibilityCounts;

    /**
     * @param array<array-key, mixed> $categoryCounts
     * @param array<array-key, mixed> $lifecycleCounts
     * @param array<array-key, mixed> $visibilityCounts
     */
    public function __construct(
        private DeveloperIntelligenceRole $role,
        private string $declarationArtifactSha256,
        private DeclarationSetIdentity $declarationSetIdentity,
        private string $generatedWiringSha256,
        private string $admissionIdentity,
        private string $grantPlanIdentity,
        private ContractVersion $extensionContractVersion,
        private int $declarationCount,
        private int $implementationCount,
        private int $decisionCount,
        private int $applicationProviderCount,
        private int $operationProviderCount,
        array $categoryCounts,
        array $lifecycleCounts,
        private int $capabilityRequestCount,
        private int $requiredCapabilityRequestCount,
        private int $optionalCapabilityRequestCount,
        private int $contributionCount,
        array $visibilityCounts,
        private string $scope = ExtensionPlatformInspection::SCOPE,
        private string $visibility = ExtensionPlatformInspection::VISIBILITY,
        private string $currentness = ExtensionPlatformInspection::CURRENTNESS,
        private string $authority = ExtensionPlatformInspection::AUTHORITY,
        private string $hostState = ExtensionPlatformInspection::HOST_STATE,
        private string $execution = ExtensionPlatformInspection::EXECUTION,
        private string $trustedCodeIsolation = ExtensionPlatformInspection::TRUSTED_CODE_ISOLATION,
    ) {
        $inspection = new ExtensionPlatformInspection($declarationArtifactSha256, $declarationSetIdentity, $generatedWiringSha256, $admissionIdentity, $grantPlanIdentity, $extensionContractVersion, $declarationCount, $implementationCount, $decisionCount, $applicationProviderCount, $operationProviderCount, $categoryCounts, $lifecycleCounts, $capabilityRequestCount, $requiredCapabilityRequestCount, $optionalCapabilityRequestCount, $contributionCount, $visibilityCounts, $scope, $visibility, $currentness, $authority, $hostState, $execution, $trustedCodeIsolation);
        $this->categoryCounts = self::copyCategories($inspection);
        $this->lifecycleCounts = self::copyLifecycles($inspection);
        $this->visibilityCounts = self::copyVisibilities($inspection);
    }

    public function role(): DeveloperIntelligenceRole
    {
        return $this->role;
    }
    public function declarationArtifactSha256(): string
    {
        return $this->declarationArtifactSha256;
    }
    public function declarationSetIdentity(): DeclarationSetIdentity
    {
        return $this->declarationSetIdentity;
    }
    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }
    public function admissionIdentity(): string
    {
        return $this->admissionIdentity;
    }
    public function grantPlanIdentity(): string
    {
        return $this->grantPlanIdentity;
    }
    public function extensionContractVersion(): ContractVersion
    {
        return $this->extensionContractVersion;
    }
    public function declarationCount(): int
    {
        return $this->declarationCount;
    }
    public function implementationCount(): int
    {
        return $this->implementationCount;
    }
    public function decisionCount(): int
    {
        return $this->decisionCount;
    }
    public function applicationProviderCount(): int
    {
        return $this->applicationProviderCount;
    }
    public function operationProviderCount(): int
    {
        return $this->operationProviderCount;
    }
    public function categoryCount(ExtensionCategory $category): int
    {
        return $this->categoryCounts[$category->value];
    }
    public function lifecycleCount(ExtensionLifecycle $lifecycle): int
    {
        return $this->lifecycleCounts[$lifecycle->value];
    }
    public function capabilityRequestCount(): int
    {
        return $this->capabilityRequestCount;
    }
    public function requiredCapabilityRequestCount(): int
    {
        return $this->requiredCapabilityRequestCount;
    }
    public function optionalCapabilityRequestCount(): int
    {
        return $this->optionalCapabilityRequestCount;
    }
    public function contributionCount(): int
    {
        return $this->contributionCount;
    }
    public function contributionVisibilityCount(ContributionVisibility $visibility): int
    {
        return $this->visibilityCounts[$visibility->value];
    }
    public function scope(): string
    {
        return $this->scope;
    }
    public function visibility(): string
    {
        return $this->visibility;
    }
    public function currentness(): string
    {
        return $this->currentness;
    }
    public function authority(): string
    {
        return $this->authority;
    }
    public function hostState(): string
    {
        return $this->hostState;
    }
    public function execution(): string
    {
        return $this->execution;
    }
    public function trustedCodeIsolation(): string
    {
        return $this->trustedCodeIsolation;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['schema' => 'kongono.extension-intelligence-view', 'version' => 1, 'role' => $this->role->value, 'declaration_artifact_sha256' => $this->declarationArtifactSha256, 'declaration_set_identity' => $this->declarationSetIdentity->value(), 'generated_wiring_sha256' => $this->generatedWiringSha256, 'admission_identity' => $this->admissionIdentity, 'grant_plan_identity' => $this->grantPlanIdentity, 'extension_contract_version' => $this->extensionContractVersion->value, 'declaration_count' => $this->declarationCount, 'implementation_count' => $this->implementationCount, 'decision_count' => $this->decisionCount, 'application_provider_count' => $this->applicationProviderCount, 'operation_provider_count' => $this->operationProviderCount, 'category_counts' => [...$this->categoryCounts], 'lifecycle_counts' => [...$this->lifecycleCounts], 'capability_request_count' => $this->capabilityRequestCount, 'required_capability_request_count' => $this->requiredCapabilityRequestCount, 'optional_capability_request_count' => $this->optionalCapabilityRequestCount, 'contribution_count' => $this->contributionCount, 'contribution_visibility_counts' => [...$this->visibilityCounts], 'scope' => $this->scope, 'visibility' => $this->visibility, 'currentness' => $this->currentness, 'authority' => $this->authority, 'host_state' => $this->hostState, 'execution' => $this->execution, 'trusted_code_isolation' => $this->trustedCodeIsolation];
    }

    /** @return array<string, int> */
    private static function copyCategories(ExtensionPlatformInspection $inspection): array
    {
        $values = [];
        foreach (ExtensionCategory::cases() as $case) {
            $values[$case->value] = $inspection->categoryCount($case);
        } return $values;
    }
    /** @return array<string, int> */
    private static function copyLifecycles(ExtensionPlatformInspection $inspection): array
    {
        $values = [];
        foreach (ExtensionLifecycle::cases() as $case) {
            $values[$case->value] = $inspection->lifecycleCount($case);
        } return $values;
    }
    /** @return array<string, int> */
    private static function copyVisibilities(ExtensionPlatformInspection $inspection): array
    {
        $values = [];
        foreach (ContributionVisibility::cases() as $case) {
            $values[$case->value] = $inspection->contributionVisibilityCount($case);
        } return $values;
    }
}
