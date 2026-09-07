<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Exception\InvalidModelInvocationAgentBridgeException;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use Kongono\ModelInvocationAgentBridge\Mapping\ProposalUsageSelector;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;

/** Projects one exact usage quantity and derives bounded output-byte evidence. */
#[Api(ApiStatus::Internal)]
final class UsageProjector
{
    public static function tokenUsage(?UsageEvidence $usage, ProposalUsageSelector $selector): int
    {
        if (null === $usage) {
            throw ProposalBridgeRejectedException::projection('usage_missing', 'usage', 0);
        }

        $quantities = $usage->quantities();
        foreach ($quantities as $quantity) {
            if ($quantity->metric()->equals($selector->metric()) && $quantity->unit()->equals($selector->unit())) {
                return $quantity->quantity();
            }
        }

        throw ProposalBridgeRejectedException::projection('usage_selector_missing', 'usage', count($quantities));
    }

    public static function outputByteUsage(ActionSuggestion $suggestion): int
    {
        $actionBytes = strlen($suggestion->name()->value());
        $argumentBytes = strlen($suggestion->arguments()->value());
        if ($actionBytes > PHP_INT_MAX - $argumentBytes) {
            throw InvalidModelInvocationAgentBridgeException::invalid('output_byte_overflow', 'output_bytes');
        }

        return $actionBytes + $argumentBytes;
    }

    private function __construct() {}
}
