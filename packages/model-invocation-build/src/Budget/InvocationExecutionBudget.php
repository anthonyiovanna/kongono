<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Budget;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;

/** Binds finite inert invocation limits without enforcing runtime behavior. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationExecutionBudget
{
    /** @var list<UsageLimit> */
    private array $usageLimits;

    /** @param list<UsageLimit> $usageLimits */
    public function __construct(
        private int $maxDurationMilliseconds,
        private int $maxAttempts,
        private int $maxRetainedEvidenceBytes,
        array $usageLimits,
        private ?CostLimit $costLimit,
    ) {
        if ($maxDurationMilliseconds < 1 || $maxDurationMilliseconds > 300_000) {
            throw InvalidInvocationPlanSourceException::invalidBudget('duration_range', 'max_duration_milliseconds');
        }
        if (1 !== $maxAttempts) {
            throw InvalidInvocationPlanSourceException::invalidBudget('attempt_count', 'max_attempts');
        }
        if ($maxRetainedEvidenceBytes < 1 || $maxRetainedEvidenceBytes > 1_048_576) {
            throw InvalidInvocationPlanSourceException::invalidBudget('retained_evidence_range', 'max_retained_evidence_bytes');
        }
        self::validateUsageLimits($usageLimits);
        $this->usageLimits = [...$usageLimits];
    }

    public function maxDurationMilliseconds(): int
    {
        return $this->maxDurationMilliseconds;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function maxRetainedEvidenceBytes(): int
    {
        return $this->maxRetainedEvidenceBytes;
    }

    /** @return list<UsageLimit> */
    public function usageLimits(): array
    {
        return [...$this->usageLimits];
    }

    public function costLimit(): ?CostLimit
    {
        return $this->costLimit;
    }

    public function equals(self $other): bool
    {
        if (
            $this->maxDurationMilliseconds !== $other->maxDurationMilliseconds
            || $this->maxAttempts !== $other->maxAttempts
            || $this->maxRetainedEvidenceBytes !== $other->maxRetainedEvidenceBytes
            || count($this->usageLimits) !== count($other->usageLimits)
            || (null === $this->costLimit) !== (null === $other->costLimit)
        ) {
            return false;
        }
        foreach ($this->usageLimits as $position => $limit) {
            if (!$limit->equals($other->usageLimits[$position])) {
                return false;
            }
        }

        $otherCostLimit = $other->costLimit;

        return null === $this->costLimit || (null !== $otherCostLimit && $this->costLimit->equals($otherCostLimit));
    }

    /** @param array<array-key, mixed> $limits */
    private static function validateUsageLimits(array $limits): void
    {
        if (!array_is_list($limits)) {
            throw InvalidInvocationPlanSourceException::invalidBudget('usage_limits_not_list', 'usage_limits', count($limits));
        }
        if (count($limits) > 16) {
            throw InvalidInvocationPlanSourceException::invalidBudget('usage_limits_count', 'usage_limits', count($limits));
        }
        $previous = null;
        foreach ($limits as $position => $limit) {
            if (!$limit instanceof UsageLimit) {
                throw InvalidInvocationPlanSourceException::invalidBudget('invalid_usage_limit', 'usage_limits', count($limits), $position);
            }
            $key = $limit->metric()->value() . '|' . $limit->unit()->value();
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                throw InvalidInvocationPlanSourceException::invalidBudget('noncanonical_usage_limit_order', 'usage_limits', count($limits), $position);
            }
            $previous = $key;
        }
    }
}
