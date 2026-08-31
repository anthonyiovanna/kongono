<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Contribution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ExtensionAdmission\Internal\AdmissionGuard;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Carries bounded inert extension-owned Graph IR awaiting admission. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionGraphContribution
{
    /** @var list<GraphNodeRecord> */
    private array $nodeRecords;
    /** @var list<GraphRelationRecord> */
    private array $relationRecords;

    /**
     * @param array<array-key, mixed> $nodeRecords
     * @param array<array-key, mixed> $relationRecords
     */
    public function __construct(
        private ExtensionIdentity $extensionIdentity,
        private ExtensionVersion $extensionVersion,
        private ContributionIdentity $contributionIdentity,
        array $nodeRecords,
        array $relationRecords,
    ) {
        $this->nodeRecords = AdmissionGuard::nodeRecords($nodeRecords);
        $this->relationRecords = AdmissionGuard::relationRecords($relationRecords);
    }

    public function extensionIdentity(): ExtensionIdentity
    {
        return $this->extensionIdentity;
    }

    public function extensionVersion(): ExtensionVersion
    {
        return $this->extensionVersion;
    }

    public function contributionIdentity(): ContributionIdentity
    {
        return $this->contributionIdentity;
    }

    /** @return list<GraphNodeRecord> */
    public function nodeRecords(): array
    {
        return [...$this->nodeRecords];
    }

    /** @return list<GraphRelationRecord> */
    public function relationRecords(): array
    {
        return [...$this->relationRecords];
    }
}
