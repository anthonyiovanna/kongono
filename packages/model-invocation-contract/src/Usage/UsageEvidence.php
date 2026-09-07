<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Usage;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;

/** Stores canonical bounded usage claims without asserting external truth. */
#[Api(ApiStatus::Experimental)]
final readonly class UsageEvidence
{
    /** @var list<UsageQuantity> */
    private array $quantities;

    /** @param list<UsageQuantity> $quantities */
    public function __construct(array $quantities)
    {
        self::validate($quantities);
        $this->quantities = [...$quantities];
    }

    /** @param array<array-key, mixed> $quantities */
    private static function validate(array $quantities): void
    {
        if (!array_is_list($quantities)) {
            throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('usage_not_list', count($quantities));
        }
        if ([] === $quantities || count($quantities) > 16) {
            throw InvalidModelInvocationContractException::contractLimitExceeded('usage_count', 16, count($quantities));
        }

        $previous = null;
        $seen = [];
        foreach ($quantities as $position => $quantity) {
            if (!$quantity instanceof UsageQuantity) {
                throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('invalid_usage_entry', count($quantities), $position);
            }
            $key = $quantity->metric()->value() . '|' . $quantity->unit()->value();
            if (isset($seen[$key])) {
                throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('duplicate_usage_entry', count($quantities), $position);
            }
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('non_canonical_usage_order', count($quantities), $position);
            }
            $seen[$key] = true;
            $previous = $key;
        }

    }

    /** @return list<UsageQuantity> */
    public function quantities(): array
    {
        return [...$this->quantities];
    }

    public function equals(self $other): bool
    {
        if (count($this->quantities) !== count($other->quantities)) {
            return false;
        }
        foreach ($this->quantities as $position => $quantity) {
            if (!$quantity->equals($other->quantities[$position])) {
                return false;
            }
        }

        return true;
    }
}
