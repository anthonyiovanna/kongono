<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Exception\InvalidExtensionContractException;

/** Centralizes exact local grammar checks without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function extensionIdentity(string $value): string
    {
        if (strlen($value) > 129 || 1 !== preg_match('/\A([a-z0-9]+(?:[._-][a-z0-9]+)*)\/([a-z0-9]+(?:[._-][a-z0-9]+)*)\z/D', $value, $matches) || strlen($matches[1]) > 64 || strlen($matches[2]) > 64) {
            throw InvalidExtensionContractException::invalidIdentity('extension_identity_grammar');
        }

        return $value;
    }

    public static function extensionVersion(string $value): string
    {
        if (!self::isSegment($value, 64)) {
            throw InvalidExtensionContractException::invalidVersion('extension_version_grammar');
        }

        return $value;
    }

    public static function capabilityIdentity(string $value): string
    {
        if (!self::isSegment($value, 128)) {
            throw InvalidExtensionContractException::invalidIdentity('capability_identity_grammar');
        }

        return $value;
    }

    public static function capabilityPurpose(string $value): string
    {
        if (!self::isSegment($value, 128)) {
            throw InvalidExtensionContractException::invalidCapabilityRequest('capability_purpose_grammar');
        }

        return $value;
    }

    public static function capabilityVersion(int $version): int
    {
        if ($version < 1 || $version > 65535) {
            throw InvalidExtensionContractException::invalidVersion('capability_version_range');
        }

        return $version;
    }

    public static function contributionIdentity(string $value): string
    {
        if (!self::isSegment($value, 128)) {
            throw InvalidExtensionContractException::invalidIdentity('contribution_identity_grammar');
        }

        return $value;
    }

    public static function contributionSchemaName(string $value): string
    {
        if (!self::isSegment($value, 128)) {
            throw InvalidExtensionContractException::invalidIdentity('contribution_schema_name_grammar');
        }

        return $value;
    }

    public static function contributionSchemaVersion(int $version): int
    {
        if ($version < 1 || $version > 65535) {
            throw InvalidExtensionContractException::invalidVersion('contribution_schema_version_range');
        }

        return $version;
    }

    public static function extensionSourceIdentity(string $value): string
    {
        if (!self::isDigest($value)) {
            throw InvalidExtensionContractException::invalidProvenance('source_identity_grammar');
        }

        return $value;
    }

    public static function provenanceDigest(string $value): string
    {
        if (!self::isDigest($value)) {
            throw InvalidExtensionContractException::invalidProvenance('source_digest_grammar');
        }

        return $value;
    }

    public static function contributionDigest(string $value): string
    {
        if (!self::isDigest($value)) {
            throw InvalidExtensionContractException::invalidContribution('contribution_source_digest_grammar');
        }

        return $value;
    }

    private static function isSegment(string $value, int $maximumBytes): bool
    {
        return strlen($value) >= 1
            && strlen($value) <= $maximumBytes
            && 1 === preg_match('/\A[a-z0-9]+(?:[._-][a-z0-9]+)*\z/D', $value);
    }

    private static function isDigest(string $value): bool
    {
        return 64 === strlen($value) && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $value);
    }
}
