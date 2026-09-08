<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Address;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;

/** Retains one ordered resolution result and exact selected member. */
#[Api(ApiStatus::Experimental)]
final readonly class ResolutionEvidence
{
    /** @var list<ResolvedAddress> */
    private array $addresses;

    /** @param array<array-key, mixed> $addresses */
    public function __construct(array $addresses, private ResolvedAddress $selectedAddress)
    {
        if (!array_is_list($addresses) || [] === $addresses || count($addresses) > 64) {
            throw InvalidEgressRuntimeException::invalid('resolution_list');
        }
        $seen = [];
        $selectedCount = 0;
        foreach ($addresses as $address) {
            if (!$address instanceof ResolvedAddress) {
                throw InvalidEgressRuntimeException::invalid('resolution_entry');
            }
            if (isset($seen[$address->value()])) {
                throw InvalidEgressRuntimeException::invalid('resolution_duplicate');
            }
            $seen[$address->value()] = true;
            if ($address->equals($selectedAddress)) {
                ++$selectedCount;
            }
        }
        if (1 !== $selectedCount) {
            throw InvalidEgressRuntimeException::invalid('selected_membership');
        }
        $this->addresses = [...$addresses];
    }

    /** @return list<ResolvedAddress> */
    public function addresses(): array
    {
        return [...$this->addresses];
    }
    public function selectedAddress(): ResolvedAddress
    {
        return $this->selectedAddress;
    }
}
