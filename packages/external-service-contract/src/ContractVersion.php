<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact neutral external-service contract shape. */
#[Api(ApiStatus::Experimental)]
final readonly class ContractVersion
{
    public const int CURRENT = 1;
}
