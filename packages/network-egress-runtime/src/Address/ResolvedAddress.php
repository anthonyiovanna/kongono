<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Address;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Internal\AddressClassifier;

/** Stores one exact canonical literal and its conservative offline classification. */
#[Api(ApiStatus::Experimental)]
final readonly class ResolvedAddress
{
    private AddressFamily $family;
    private AddressCategory $category;

    public function __construct(private string $value)
    {
        [$this->family, $this->category] = AddressClassifier::classify($value);
    }

    public function value(): string
    {
        return $this->value;
    }
    public function family(): AddressFamily
    {
        return $this->family;
    }
    public function category(): AddressCategory
    {
        return $this->category;
    }
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
