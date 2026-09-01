<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns one provider-neutral proposal attempt and explicit operation cleanup. */
#[Api(ApiStatus::Experimental)]
interface AgentProposalPort
{
    public function propose(AgentProposalRequest $request): AgentProposalResult;

    public function close(): void;
}
