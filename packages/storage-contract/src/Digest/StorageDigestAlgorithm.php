<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Digest;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one supported content-digest algorithm. */
#[Api(ApiStatus::Experimental)]
enum StorageDigestAlgorithm: string
{
    case Sha256 = 'sha256';
    case Sha384 = 'sha384';
    case Sha512 = 'sha512';
}
