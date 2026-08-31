<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Declaration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Version\ExtensionVersion;

/** Aggregates one locally valid canonical declaration without admitting an extension. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionDeclaration
{
    private ContractVersion $contractVersion;
    private ExtensionIdentity $identity;
    private ExtensionVersion $version;
    private ExtensionCategory $category;
    private ExtensionProvenance $provenance;
    private ExtensionLifecycle $lifecycle;
    /** @var list<CapabilityRequest> */
    private array $capabilityRequests;
    /** @var list<ContributionDeclaration> */
    private array $contributions;

    /**
     * @param list<CapabilityRequest> $capabilityRequests
     * @param list<ContributionDeclaration> $contributions
     */
    public function __construct(
        ContractVersion $contractVersion,
        ExtensionIdentity $identity,
        ExtensionVersion $version,
        ExtensionCategory $category,
        ExtensionProvenance $provenance,
        ExtensionLifecycle $lifecycle,
        array $capabilityRequests,
        array $contributions,
    ) {
        self::validateCapabilityRequests($capabilityRequests);
        self::validateContributions($contributions);
        $this->contractVersion = $contractVersion;
        $this->identity = $identity;
        $this->version = $version;
        $this->category = $category;
        $this->provenance = $provenance;
        $this->lifecycle = $lifecycle;
        $this->capabilityRequests = [...$capabilityRequests];
        $this->contributions = [...$contributions];
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function identity(): ExtensionIdentity
    {
        return $this->identity;
    }

    public function version(): ExtensionVersion
    {
        return $this->version;
    }

    public function category(): ExtensionCategory
    {
        return $this->category;
    }

    public function provenance(): ExtensionProvenance
    {
        return $this->provenance;
    }

    public function lifecycle(): ExtensionLifecycle
    {
        return $this->lifecycle;
    }

    /** @return list<CapabilityRequest> */
    public function capabilityRequests(): array
    {
        return [...$this->capabilityRequests];
    }

    /** @return list<ContributionDeclaration> */
    public function contributions(): array
    {
        return [...$this->contributions];
    }

    /** @param array<array-key, mixed> $requests */
    private static function validateCapabilityRequests(array $requests): void
    {
        if (!array_is_list($requests)) {
            throw InvalidExtensionContractException::invalidDeclaration('capability_requests_not_list', count($requests));
        }
        if (count($requests) > 64) {
            throw InvalidExtensionContractException::contractLimitExceeded('capability_request_count', count($requests));
        }

        $previousKey = null;
        $seen = [];
        foreach ($requests as $position => $request) {
            if (!$request instanceof CapabilityRequest) {
                throw InvalidExtensionContractException::invalidDeclaration('invalid_capability_request_entry', count($requests), $position);
            }
            $key = self::capabilityKey($request);
            if (isset($seen[$key])) {
                throw InvalidExtensionContractException::invalidDeclaration('duplicate_capability_request', count($requests), $position);
            }
            if (null !== $previousKey && strcmp($previousKey, $key) >= 0) {
                throw InvalidExtensionContractException::invalidDeclaration('non_canonical_capability_request_order', count($requests), $position);
            }
            $seen[$key] = true;
            $previousKey = $key;
        }
    }

    /** @param array<array-key, mixed> $contributions */
    private static function validateContributions(array $contributions): void
    {
        if (!array_is_list($contributions)) {
            throw InvalidExtensionContractException::invalidDeclaration('contributions_not_list', count($contributions));
        }
        if (count($contributions) > 128) {
            throw InvalidExtensionContractException::contractLimitExceeded('contribution_count', count($contributions));
        }

        $previousKey = null;
        $identities = [];
        foreach ($contributions as $position => $contribution) {
            if (!$contribution instanceof ContributionDeclaration) {
                throw InvalidExtensionContractException::invalidDeclaration('invalid_contribution_entry', count($contributions), $position);
            }
            $identity = $contribution->identity()->value();
            if (isset($identities[$identity])) {
                throw InvalidExtensionContractException::invalidDeclaration('duplicate_contribution_identity', count($contributions), $position);
            }
            $key = self::contributionKey($contribution);
            if (null !== $previousKey && strcmp($previousKey, $key) >= 0) {
                throw InvalidExtensionContractException::invalidDeclaration('non_canonical_contribution_order', count($contributions), $position);
            }
            $identities[$identity] = true;
            $previousKey = $key;
        }
    }

    private static function capabilityKey(CapabilityRequest $request): string
    {
        return $request->identity()->value() . "\0"
            . (string) $request->identity()->version() . "\0"
            . $request->purpose()->value() . "\0"
            . $request->requirement()->value;
    }

    private static function contributionKey(ContributionDeclaration $contribution): string
    {
        return $contribution->identity()->value() . "\0"
            . $contribution->schema()->name() . "\0"
            . (string) $contribution->schema()->version() . "\0"
            . $contribution->visibility()->value;
    }
}
