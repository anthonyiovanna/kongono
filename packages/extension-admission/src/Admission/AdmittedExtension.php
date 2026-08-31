<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Admission;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionAdmission\Internal\AdmissionGuard;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Identifies one admitted exact selection and its declared contribution identities. */
#[Api(ApiStatus::Experimental)]
final readonly class AdmittedExtension
{
    /** @var list<ContributionIdentity> */
    private array $contributionIdentities;

    /** @param array<array-key, mixed> $contributionIdentities */
    public function __construct(
        private ExtensionIdentity $identity,
        private ExtensionVersion $version,
        array $contributionIdentities,
    ) {
        $this->contributionIdentities = AdmissionGuard::contributionIdentities($contributionIdentities);
    }

    public function identity(): ExtensionIdentity
    {
        return $this->identity;
    }

    public function version(): ExtensionVersion
    {
        return $this->version;
    }

    /** @return list<ContributionIdentity> */
    public function contributionIdentities(): array
    {
        return [...$this->contributionIdentities];
    }
}
