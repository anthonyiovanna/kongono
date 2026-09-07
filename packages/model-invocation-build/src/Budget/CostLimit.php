<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Budget;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;

/** Carries one inert application-owned cost ceiling without pricing authority. */
#[Api(ApiStatus::Experimental)]
final readonly class CostLimit
{
    public function __construct(
        private CurrencyCode $currency,
        private DecimalAmount $maximumAmount,
    ) {}

    public function currency(): CurrencyCode
    {
        return $this->currency;
    }

    public function maximumAmount(): DecimalAmount
    {
        return $this->maximumAmount;
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency)
            && $this->maximumAmount->equals($other->maximumAmount);
    }
}
