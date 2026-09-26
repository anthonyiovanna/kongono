<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use Kongono\StorageContract\Pagination\StorageContinuationToken;

/** Encodes checksum-protected profile-bound list continuation evidence. */
#[Api(ApiStatus::Internal)]
final class LocalContinuationCodec
{
    public static function encode(StorageAdapterDescriptor $descriptor, string $namespace, string $logical): StorageContinuationToken
    {
        $payload = CanonicalJson::encode(['v' => 1,'adapter' => $descriptor->identity()->canonical(),'artifact' => $descriptor->artifactIdentity()->value(),'profile' => $descriptor->profileName()->value(),'namespace' => $namespace,'last' => $logical]);
        $bytes = $payload . "\0" . hash('sha256', "kongono.storage.local.continuation.v1\0" . $payload);

        return new StorageContinuationToken(rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='));
    }

    public static function decode(StorageContinuationToken $token, StorageAdapterDescriptor $descriptor, string $namespace): string
    {
        $value = $token->value();
        $padding = (4 - strlen($value) % 4) % 4;
        $bytes = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
        if (false === $bytes || !str_contains($bytes, "\0")) {
            throw LocalStorageOperationException::failed('continuation_invalid', 'list');
        }
        [$payload,$checksum] = explode("\0", $bytes, 2);
        if (!hash_equals(hash('sha256', "kongono.storage.local.continuation.v1\0" . $payload), $checksum)) {
            throw LocalStorageOperationException::failed('continuation_invalid', 'list');
        }
        try {
            $row = CanonicalJson::decode($payload);
        } catch (JsonException $error) {
            throw LocalStorageOperationException::failed('continuation_invalid', 'list', $error);
        }
        if (array_keys($row) !== ['v','adapter','artifact','profile','namespace','last'] || 1 !== $row['v'] || $descriptor->identity()->canonical() !== $row['adapter'] || $descriptor->artifactIdentity()->value() !== $row['artifact'] || $descriptor->profileName()->value() !== $row['profile'] || $namespace !== $row['namespace'] || !is_string($row['last'])) {
            throw LocalStorageOperationException::failed('continuation_mismatch', 'list');
        }

        return $row['last'];
    }

    private function __construct() {}
}
