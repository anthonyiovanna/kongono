<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Exception\InvalidCacheApplicationException;
use Kongono\CacheApplication\Internal\StrictJsonDecoder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use Throwable;

/** Converts one strict bounded Version 1 document into accepted lower profile values. */
#[Api(ApiStatus::Internal)]
final class CacheProfileSourceLoader
{
    public function load(string $bytes): CacheProfileSet
    {
        try {
            $model = StrictJsonDecoder::decodeObject($bytes);
            $typed = json_decode($bytes, false, 8, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            if (!$typed instanceof \stdClass || !property_exists($typed, 'profiles') || !is_array($typed->profiles)) {
                throw InvalidCacheApplicationException::invalid('invalid_profile');
            }
            $this->keys($model, ['schema', 'profiles']);
            if ('kongono/cache-application-profile-source/1' !== $model['schema']) {
                throw InvalidCacheApplicationException::invalid('invalid_schema');
            }
            $rows = $model['profiles'];
            if (!is_array($rows) || !array_is_list($rows) || count($rows) > 256) {
                throw InvalidCacheApplicationException::invalid('invalid_profile');
            }
            $profiles = [];
            foreach ($rows as $position => $row) {
                $typedRow = $typed->profiles[$position] ?? null;
                if (!$typedRow instanceof \stdClass || !property_exists($typedRow, 'codec') || !$typedRow->codec instanceof \stdClass) {
                    throw InvalidCacheApplicationException::invalid('invalid_profile');
                }
                if (!is_array($row) || array_is_list($row)) {
                    throw InvalidCacheApplicationException::invalid('invalid_profile');
                }
                $row = $this->object($row);
                $this->keys($row, ['name', 'contract_version', 'namespace', 'codec', 'lifetime_microseconds', 'max_value_bytes']);
                $codec = $row['codec'];
                if (!is_array($codec) || array_is_list($codec)) {
                    throw InvalidCacheApplicationException::invalid('invalid_profile');
                }
                $codec = $this->object($codec);
                $this->keys($codec, ['name', 'version']);
                if (!is_string($row['name']) || 1 !== $row['contract_version'] || !is_string($row['namespace']) || !is_string($codec['name']) || !is_int($codec['version']) || !is_int($row['lifetime_microseconds']) || !is_int($row['max_value_bytes'])) {
                    throw InvalidCacheApplicationException::invalid('invalid_profile');
                }
                $profiles[] = new CacheProfileDeclaration(
                    ContractVersion::Version1,
                    new CacheProfileName($row['name']),
                    new CacheNamespace($row['namespace']),
                    new CacheCodecIdentity($codec['name'], $codec['version']),
                    new CacheLifetime($row['lifetime_microseconds']),
                    new CacheProfileValueLimit($row['max_value_bytes']),
                );
            }
            return new CacheProfileSet($profiles);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidCacheApplicationException) {
                throw $failure;
            }
            throw InvalidCacheApplicationException::invalid('invalid_json', $failure);
        }
    }

    /**
     * @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private function keys(array $object, array $expected): void
    {
        $actual = array_keys($object);
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            throw InvalidCacheApplicationException::invalid('invalid_schema');
        }
    }

    /**
     * @param array<array-key, mixed> $value
     * @return array<string, mixed>
     */
    private function object(array $value): array
    {
        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidCacheApplicationException::invalid('invalid_schema');
            }
            $object[$key] = $entry;
        }
        return $object;
    }
}
