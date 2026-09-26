<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;

/** Owns fixed build-profile and canonical-model invariants. */
#[Api(ApiStatus::Internal)]
final class BuildGuard
{
    public const string SOURCE_PROFILE = 'kongono/storage-profile-source/1';
    public const string BUILD_PROFILE = 'kongono/storage-profile-artifact/1';
    public const string BUILD_DOMAIN = 'kongono/storage-profile-build/1';

    /**
     * @param list<StorageProfileDeclaration> $profiles
     * @return list<array{name: string, contract_version: int, namespace: string, capabilities: list<string>, digest_algorithm: ?string, limits: array{max_chunk_bytes: int, max_object_bytes: int, max_list_page_size: int}}>
     */
    public static function profileRows(array $profiles): array
    {
        return array_map(static fn(StorageProfileDeclaration $profile): array => [
            'name' => $profile->name()->value(),
            'contract_version' => $profile->contractVersion()->value,
            'namespace' => $profile->namespace()->value(),
            'capabilities' => array_map(
                static fn(\Kongono\StorageContract\Capability\StorageCapability $capability): string => $capability->value,
                $profile->capabilities()->capabilities(),
            ),
            'digest_algorithm' => $profile->digestAlgorithm()?->value,
            'limits' => [
                'max_chunk_bytes' => $profile->limits()->maxChunkBytes(),
                'max_object_bytes' => $profile->limits()->maxObjectBytes(),
                'max_list_page_size' => $profile->limits()->maxListPageSize(),
            ],
        ], $profiles);
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    public static function exactKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_schema');
        }
    }

    private function __construct() {}
}
