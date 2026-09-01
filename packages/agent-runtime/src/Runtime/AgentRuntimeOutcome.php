<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies the bounded operation result without converting evidence into authority. */
#[Api(ApiStatus::Experimental)]
enum AgentRuntimeOutcome: string
{
    case Performed = 'performed';
    case CapabilityFailed = 'capability_failed';
    case Cancelled = 'cancelled';
    case BudgetExceeded = 'budget_exceeded';
    case AuthorityDenied = 'authority_denied';
}
