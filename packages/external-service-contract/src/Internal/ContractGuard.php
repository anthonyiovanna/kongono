<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;

/** Centralizes exact local grammar checks without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function identity(string $value, string $violation): string
    {
        $length = strlen($value);
        if ($length > 128) {
            throw InvalidExternalServiceContractException::contractLimitExceeded('identity_bytes', 128);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/D', $value)) {
            throw InvalidExternalServiceContractException::invalidIdentity($violation);
        }

        return $value;
    }

    public static function credentialReference(string $value): string
    {
        $length = strlen($value);
        if ($length > 256) {
            throw InvalidExternalServiceContractException::contractLimitExceeded('credential_reference_bytes', 256);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9._\/-]*[a-z0-9])?\z/D', $value)) {
            throw InvalidExternalServiceContractException::invalidCredentialReference('credential_reference_grammar');
        }

        return $value;
    }

    public static function endpointBasePath(string $value): string
    {
        if ('' === $value) {
            return $value;
        }
        if (strlen($value) > 1024) {
            throw InvalidExternalServiceContractException::contractLimitExceeded('endpoint_base_path_bytes', 1024);
        }
        $segment = "(?:[A-Za-z0-9._\\~!$&'()*+,;=:@-]|%[0-9A-F]{2})+";
        if (1 !== preg_match("~\\A/{$segment}(?:/{$segment})*\\z~D", $value)) {
            throw InvalidExternalServiceContractException::invalidEndpoint('endpoint_base_path_grammar');
        }
        foreach (explode('/', substr($value, 1)) as $part) {
            if ('.' === $part || '..' === $part) {
                throw InvalidExternalServiceContractException::invalidEndpoint('endpoint_base_path_grammar');
            }
        }

        return $value;
    }

    public static function reason(string $value): string
    {
        $length = strlen($value);
        if ($length > 64) {
            throw InvalidExternalServiceContractException::contractLimitExceeded('failure_reason_bytes', 64);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z0-9]+(?:_[a-z0-9]+)*\z/D', $value)) {
            throw InvalidExternalServiceContractException::invalidResult('failure_reason_grammar');
        }

        return $value;
    }
}
