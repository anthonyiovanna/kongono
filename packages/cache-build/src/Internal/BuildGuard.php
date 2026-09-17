<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;

/** Owns fixed build-profile and canonical-model invariants. */
#[Api(ApiStatus::Internal)]
final class BuildGuard
{
    public const string BUILD_PROFILE = 'kongono/cache-profile-artifact/1';

    /**
     * @param list<CacheProfileDeclaration> $profiles
     * @return list<array{name: string, contract_version: int, namespace: string, codec: array{name: string, version: int}, lifetime_microseconds: int, max_value_bytes: int}>
     */
    public static function profileRows(array $profiles): array
    {
        return array_map(static fn(CacheProfileDeclaration $profile): array => [
            'name' => $profile->name()->value(),
            'contract_version' => $profile->contractVersion()->value,
            'namespace' => $profile->namespace()->value(),
            'codec' => [
                'name' => $profile->codec()->name(),
                'version' => $profile->codec()->version(),
            ],
            'lifetime_microseconds' => $profile->lifetime()->microseconds(),
            'max_value_bytes' => $profile->valueLimit()->bytes(),
        ], $profiles);
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    public static function exactKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_schema');
        }
    }

    private function __construct() {}
}
