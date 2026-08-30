<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;

/** Centralizes the exact lowercase SHA-256 grammar without exposing a generic identity. */
#[Api(ApiStatus::Internal)]
final class IdentityGuard
{
    public static function identityDigest(string $digest): string
    {
        if (!self::isDigest($digest)) {
            throw InvalidGraphContractException::identity('digest_grammar');
        }

        return $digest;
    }

    public static function isDigest(string $digest): bool
    {
        return 64 === strlen($digest) && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $digest);
    }
}
