<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Cost;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries bounded cost evidence without billing, settlement, or enforcement truth. */
#[Api(ApiStatus::Experimental)]
final readonly class CostEvidence
{
    public function __construct(
        private CostEvidenceKind $kind,
        private CurrencyCode $currency,
        private DecimalAmount $amount,
    ) {}

    public function kind(): CostEvidenceKind
    {
        return $this->kind;
    }

    public function currency(): CurrencyCode
    {
        return $this->currency;
    }

    public function amount(): DecimalAmount
    {
        return $this->amount;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind && $this->currency->equals($other->currency) && $this->amount->equals($other->amount);
    }
}
