<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionInspection\Exception\InvalidExtensionInspectionValueException;

/** Carries only bounded identities, aggregate counts, and honest limitation tokens. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionPlatformInspection
{
    public const string SCOPE = 'extension-platform-v1';
    public const string VISIBILITY = 'safe-aggregate-only';
    public const string CURRENTNESS = 'not-established';
    public const string AUTHORITY = 'evidence-only';
    public const string HOST_STATE = 'not-observed';
    public const string EXECUTION = 'not-observed';
    public const string TRUSTED_CODE_ISOLATION = 'not-sandboxed';

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
        private string $scope = self::SCOPE,
        private string $visibility = self::VISIBILITY,
        private string $currentness = self::CURRENTNESS,
        private string $authority = self::AUTHORITY,
        private string $hostState = self::HOST_STATE,
        private string $execution = self::EXECUTION,
        private string $trustedCodeIsolation = self::TRUSTED_CODE_ISOLATION,
    ) {
        foreach ([$declarationArtifactSha256, $generatedWiringSha256, $admissionIdentity, $grantPlanIdentity] as $identity) {
            if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $identity)) {
                throw InvalidExtensionInspectionValueException::value('identity');
            }
        }
        $totals = [$declarationCount, $implementationCount, $decisionCount, $applicationProviderCount, $operationProviderCount, $capabilityRequestCount, $requiredCapabilityRequestCount, $optionalCapabilityRequestCount, $contributionCount];
        if (array_any($totals, static fn(int $count): bool => $count < 0)) {
            throw InvalidExtensionInspectionValueException::value('count');
        }
        $this->categoryCounts = self::counts($categoryCounts, array_map(static fn(ExtensionCategory $case): string => $case->value, ExtensionCategory::cases()), 'category_counts');
        $this->lifecycleCounts = self::counts($lifecycleCounts, array_map(static fn(ExtensionLifecycle $case): string => $case->value, ExtensionLifecycle::cases()), 'lifecycle_counts');
        $this->visibilityCounts = self::counts($visibilityCounts, array_map(static fn(ContributionVisibility $case): string => $case->value, ContributionVisibility::cases()), 'visibility_counts');
        if (
            $declarationCount !== array_sum($this->categoryCounts)
            || $declarationCount !== array_sum($this->lifecycleCounts)
            || $capabilityRequestCount !== $requiredCapabilityRequestCount + $optionalCapabilityRequestCount
            || $contributionCount !== array_sum($this->visibilityCounts)
            || $applicationProviderCount + $operationProviderCount > $decisionCount
        ) {
            throw InvalidExtensionInspectionValueException::aggregate('count');
        }
        if (self::SCOPE !== $scope || self::VISIBILITY !== $visibility || self::CURRENTNESS !== $currentness || self::AUTHORITY !== $authority || self::HOST_STATE !== $hostState || self::EXECUTION !== $execution || self::TRUSTED_CODE_ISOLATION !== $trustedCodeIsolation) {
            throw InvalidExtensionInspectionValueException::value('semantic_tokens');
        }
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
    public function capabilityRequirementCount(CapabilityRequirement $requirement): int
    {
        return CapabilityRequirement::Required === $requirement ? $this->requiredCapabilityRequestCount : $this->optionalCapabilityRequestCount;
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

    /**
     * @param array<array-key, mixed> $counts
     * @param list<string> $expected
     * @return array<string, int>
     */
    private static function counts(array $counts, array $expected, string $component): array
    {
        if ($expected !== array_keys($counts)) {
            throw InvalidExtensionInspectionValueException::value($component);
        }
        $accepted = [];
        foreach ($expected as $key) {
            if (!is_int($counts[$key]) || $counts[$key] < 0) {
                throw InvalidExtensionInspectionValueException::value($component);
            }
            $accepted[$key] = $counts[$key];
        }
        return $accepted;
    }
}
