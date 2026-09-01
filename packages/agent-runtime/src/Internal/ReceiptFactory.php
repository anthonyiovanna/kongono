<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Internal;

use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentPrincipalContract\Receipt\ReceiptIdentity;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Derives deterministic bounded receipt claims from fixed typed operation evidence. */
#[Api(ApiStatus::Internal)]
final class ReceiptFactory
{
    public static function create(ActionProposal $proposal, ReceiptOutcome $outcome, string $adapterEvidenceSha256, AgentRuntimeUsage $usage): AgentReceipt
    {
        $fields = [
            'kongono.agent-runtime-receipt/1',
            $outcome->value,
            $proposal->principal()->reference()->kind()->value,
            $proposal->principal()->reference()->id()->value(),
            $proposal->operation()->value(),
            $proposal->identity()->value(),
            RuntimeGuard::digest($adapterEvidenceSha256),
            ...array_map(static fn(int $value): string => (string) $value, $usage->values()),
        ];
        $canonical = implode('', array_map(static fn(string $value): string => strlen($value) . ':' . $value . ';', $fields));
        $evidence = hash('sha256', 'evidence:' . $canonical);
        $identity = new ReceiptIdentity('receipt.' . hash('sha256', 'identity:' . $canonical));

        return new AgentReceipt(
            $identity,
            $proposal->principal(),
            $proposal->operation(),
            $proposal->identity(),
            $outcome,
            $evidence,
            $proposal->provenance(),
            $proposal->visibility(),
        );
    }

    private function __construct() {}
}
