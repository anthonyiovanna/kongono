<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Usage;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Names one usage metric claim without establishing tokenization truth. */
#[Api(ApiStatus::Experimental)]
final readonly class UsageMetricName
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'usage_metric_name_grammar');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
