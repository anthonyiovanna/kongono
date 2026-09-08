<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;

/** Centralizes exact local grammar checks without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function identity(string $value, string $violation): string
    {
        $length = strlen($value);
        if ($length < 1 || $length > 128 || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/D', $value)) {
            throw InvalidNetworkEgressContractException::invalidIdentity($violation);
        }

        return $value;
    }

    public static function sha256(string $value): string
    {
        if (1 !== preg_match('/\A[0-9A-F]{64}\z/D', $value)) {
            throw InvalidNetworkEgressContractException::invalidReference('policy_digest_grammar');
        }

        return $value;
    }

    public static function reason(string $value): string
    {
        $length = strlen($value);
        if ($length > 64) {
            throw InvalidNetworkEgressContractException::contractLimitExceeded('failure_reason_bytes', 64);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z0-9]+(?:_[a-z0-9]+)*\z/D', $value)) {
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt('failure_reason_grammar');
        }

        return $value;
    }
}
