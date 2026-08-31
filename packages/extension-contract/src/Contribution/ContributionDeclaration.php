<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Contribution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Declares bounded contribution metadata without payload or authoritative Graph status. */
#[Api(ApiStatus::Experimental)]
final readonly class ContributionDeclaration
{
    private string $sourceSha256;

    public function __construct(
        private ContributionIdentity $identity,
        private ContributionSchema $schema,
        private ContributionVisibility $visibility,
        string $sourceSha256,
    ) {
        $this->sourceSha256 = ContractGuard::contributionDigest($sourceSha256);
    }

    public function identity(): ContributionIdentity
    {
        return $this->identity;
    }

    public function schema(): ContributionSchema
    {
        return $this->schema;
    }

    public function visibility(): ContributionVisibility
    {
        return $this->visibility;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
}
