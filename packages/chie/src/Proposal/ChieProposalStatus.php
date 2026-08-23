<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the only Phase 4G proposal state: an unapplied review candidate. */
#[Api(ApiStatus::Experimental)]
enum ChieProposalStatus: string
{
    case Candidate = 'candidate';
}
