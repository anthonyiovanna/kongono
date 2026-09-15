<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Delivery;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one closed integrity digest algorithm. */
#[Api(ApiStatus::Experimental)]
enum IntegrityAlgorithm: string
{
    case Sha256 = 'sha256';
    case Sha384 = 'sha384';
    case Sha512 = 'sha512';
}
