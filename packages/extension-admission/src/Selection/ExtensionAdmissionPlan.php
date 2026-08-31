<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Selection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Conflict\ContributionConflictResolution;
use Kongono\ExtensionAdmission\Internal\AdmissionGuard;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;

/** Holds exact application-owned selection and conflict evidence for one declaration set. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionAdmissionPlan
{
    /** @var list<ExtensionSelection> */
    private array $selections;
    /** @var list<ContributionConflictResolution> */
    private array $conflictResolutions;

    /**
     * @param array<array-key, mixed> $selections
     * @param array<array-key, mixed> $conflictResolutions
     */
    public function __construct(
        private DeclarationSetIdentity $declarationSetIdentity,
        array $selections,
        array $conflictResolutions,
    ) {
        $this->selections = AdmissionGuard::selections($selections);
        $this->conflictResolutions = AdmissionGuard::resolutions($conflictResolutions);
    }

    public function declarationSetIdentity(): DeclarationSetIdentity
    {
        return $this->declarationSetIdentity;
    }

    /** @return list<ExtensionSelection> */
    public function selections(): array
    {
        return [...$this->selections];
    }

    /** @return list<ContributionConflictResolution> */
    public function conflictResolutions(): array
    {
        return [...$this->conflictResolutions];
    }
}
