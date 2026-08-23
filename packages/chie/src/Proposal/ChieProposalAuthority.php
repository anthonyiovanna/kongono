<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares the one closed proposal operation, rule, and caller disclosure assertion. */
#[Api(ApiStatus::Experimental)]
final readonly class ChieProposalAuthority
{
    public function operation(): string
    {
        return 'propose';
    }

    public function rule(): string
    {
        return 'exact-line-replacement-v1';
    }

    public function disclosure(): string
    {
        return 'safe';
    }
}
