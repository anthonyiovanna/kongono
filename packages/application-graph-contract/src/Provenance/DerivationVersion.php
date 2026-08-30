<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Provenance;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact deterministic derivation contract version. */
#[Api(ApiStatus::Experimental)]
enum DerivationVersion: int
{
    case Version1 = 1;
}
