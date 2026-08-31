<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Conflict;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;

/** Records one application-owned winner for one exact contribution conflict. */
#[Api(ApiStatus::Experimental)]
final readonly class ContributionConflictResolution
{
    public function __construct(
        private ContributionIdentity $contributionIdentity,
        private ExtensionIdentity $winningExtensionIdentity,
    ) {}

    public function contributionIdentity(): ContributionIdentity
    {
        return $this->contributionIdentity;
    }

    public function winningExtensionIdentity(): ExtensionIdentity
    {
        return $this->winningExtensionIdentity;
    }
}
