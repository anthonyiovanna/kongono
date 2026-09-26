<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Exception\InvalidStorageContractException;

/** Centralizes exact local validation without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function storageNamespace(string $value): string
    {
        return self::canonicalToken($value, StorageContractBounds::MAX_NAMESPACE_BYTES, 'namespace_bytes', 'storage_namespace_grammar');
    }

    public static function logicalObjectIdentity(string $value): string
    {
        return self::canonicalToken($value, StorageContractBounds::MAX_LOGICAL_IDENTITY_BYTES, 'logical_identity_bytes', 'logical_object_identity_grammar');
    }

    public static function storageOperationIdentity(string $value): string
    {
        return self::canonicalToken($value, StorageContractBounds::MAX_OPERATION_ID_BYTES, 'operation_identity_bytes', 'operation_identity_grammar');
    }

    public static function byteCount(int $value): int
    {
        if ($value < 0) {
            throw InvalidStorageContractException::invalidContentEvidence('byte_count_range');
        }
        if ($value > StorageContractBounds::MAX_OBJECT_BYTES) {
            throw InvalidStorageContractException::contractBoundExceeded('object_bytes', StorageContractBounds::MAX_OBJECT_BYTES);
        }

        return $value;
    }

    public static function chunk(string $bytes): void
    {
        $length = strlen($bytes);
        if (0 === $length) {
            throw InvalidStorageContractException::invalidContentEvidence('chunk_empty');
        }
        if ($length > StorageContractBounds::MAX_CHUNK_BYTES) {
            throw InvalidStorageContractException::contractBoundExceeded('chunk_bytes', StorageContractBounds::MAX_CHUNK_BYTES);
        }
    }

    public static function mediaType(string $value): string
    {
        $length = strlen($value);
        if ($length > StorageContractBounds::MAX_MEDIA_TYPE_BYTES) {
            throw InvalidStorageContractException::contractBoundExceeded('media_type_bytes', StorageContractBounds::MAX_MEDIA_TYPE_BYTES);
        }
        if ($length < 3 || 1 !== preg_match('/\A[a-z][a-z0-9!#$&^_.+-]*\/[a-z0-9][a-z0-9!#$&^_.+-]*\z/D', $value)) {
            throw InvalidStorageContractException::invalidContentEvidence('media_type_grammar');
        }

        return $value;
    }

    public static function contentDigest(StorageDigestAlgorithm $algorithm, string $digest): string
    {
        $length = match ($algorithm) {
            StorageDigestAlgorithm::Sha256 => 64,
            StorageDigestAlgorithm::Sha384 => 96,
            StorageDigestAlgorithm::Sha512 => 128,
        };
        if (1 !== preg_match('/\A[a-f0-9]{' . $length . '}\z/D', $digest)) {
            throw InvalidStorageContractException::invalidContentEvidence('digest_grammar', $algorithm->value);
        }

        return $digest;
    }

    public static function metadataName(string $value): string
    {
        $length = strlen($value);
        if ($length > StorageContractBounds::MAX_METADATA_NAME_BYTES) {
            throw InvalidStorageContractException::contractBoundExceeded('metadata_name_bytes', StorageContractBounds::MAX_METADATA_NAME_BYTES);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidStorageContractException::invalidContentEvidence('metadata_name_grammar');
        }

        return $value;
    }

    public static function metadataValue(string $value): string
    {
        $length = strlen($value);
        if ($length > StorageContractBounds::MAX_METADATA_VALUE_BYTES) {
            throw InvalidStorageContractException::contractBoundExceeded('metadata_value_bytes', StorageContractBounds::MAX_METADATA_VALUE_BYTES);
        }
        if (1 !== preg_match('//u', $value)) {
            throw InvalidStorageContractException::invalidContentEvidence('metadata_value_utf8');
        }
        if (1 === preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw InvalidStorageContractException::invalidContentEvidence('metadata_value_control');
        }

        return $value;
    }

    public static function objectVersion(string $value): string
    {
        return self::opaqueToken($value, StorageContractBounds::MAX_VERSION_BYTES, 'version_bytes', 'object_version_grammar');
    }

    public static function continuationToken(string $value): string
    {
        return self::opaqueToken($value, StorageContractBounds::MAX_CONTINUATION_BYTES, 'continuation_bytes', 'continuation_token_grammar');
    }

    private static function canonicalToken(string $value, int $limit, string $boundViolation, string $grammarViolation): string
    {
        $length = strlen($value);
        if ($length > $limit) {
            throw InvalidStorageContractException::contractBoundExceeded($boundViolation, $limit);
        }
        if ($length < 1 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidStorageContractException::invalidIdentity($grammarViolation);
        }

        return $value;
    }

    private static function opaqueToken(string $value, int $limit, string $boundViolation, string $grammarViolation): string
    {
        $length = strlen($value);
        if ($length > $limit) {
            throw InvalidStorageContractException::contractBoundExceeded($boundViolation, $limit);
        }
        if ($length < 1 || 1 !== preg_match('/\A[A-Za-z0-9_-]+\z/D', $value)) {
            throw InvalidStorageContractException::invalidContentEvidence($grammarViolation);
        }

        return $value;
    }
}
