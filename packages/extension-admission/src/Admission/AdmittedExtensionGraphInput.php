<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Admission;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphRelationRecord;
use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionAdmission\Identity\AdmissionIdentity;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;

/** Returns validated canonical IR evidence without returning an authoritative Graph. */
#[Api(ApiStatus::Experimental)]
final readonly class AdmittedExtensionGraphInput
{
    /**
     * @param list<AdmittedExtension> $admittedExtensions
     * @param list<ContributionConflictResolution> $appliedConflictResolutions
     * @param list<GraphNodeRecord> $nodeRecords
     * @param list<GraphRelationRecord> $relationRecords
     */
    public function __construct(
        private DeclarationSetIdentity $declarationSetIdentity,
        private AdmissionIdentity $admissionIdentity,
        private array $admittedExtensions,
        private array $appliedConflictResolutions,
        private array $nodeRecords,
        private array $relationRecords,
    ) {}

    public function declarationSetIdentity(): DeclarationSetIdentity
    {
        return $this->declarationSetIdentity;
    }
    public function admissionIdentity(): AdmissionIdentity
    {
        return $this->admissionIdentity;
    }
    /** @return list<AdmittedExtension> */
    public function admittedExtensions(): array
    {
        return [...$this->admittedExtensions];
    }
    /** @return list<ContributionConflictResolution> */
    public function appliedConflictResolutions(): array
    {
        return [...$this->appliedConflictResolutions];
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
