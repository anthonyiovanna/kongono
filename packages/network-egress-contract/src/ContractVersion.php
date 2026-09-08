<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact neutral network-egress contract shape. */
#[Api(ApiStatus::Experimental)]
final readonly class ContractVersion
{
    public const string CURRENT = '1';
}
