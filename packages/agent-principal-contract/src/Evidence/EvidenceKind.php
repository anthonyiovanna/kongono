<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Evidence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies an external evidence reference without accepting its claims. */
#[Api(ApiStatus::Experimental)]
enum EvidenceKind: string
{
    case Authentication = 'authentication';
    case Authorization = 'authorization';
    case Delegation = 'delegation';
    case Consent = 'consent';
    case Declaration = 'declaration';
    case Policy = 'policy';
}
