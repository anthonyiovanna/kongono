<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Exception\InvalidStorageApplicationException;
use Kongono\StorageApplication\Internal\StrictJsonDecoder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;
use Throwable;

/** Converts one strict bounded Version 1 source into accepted neutral profiles. */
#[Api(ApiStatus::Internal)]
final class StorageProfileSourceLoader
{
    public function load(string $bytes): StorageProfileSet
    {
        try {
            if (strlen($bytes) < 1 || strlen($bytes) > 1_048_576 || str_starts_with($bytes, "\xEF\xBB\xBF") || 1 !== preg_match('//u', $bytes)) {
                throw InvalidStorageApplicationException::invalid('invalid_json');
            }
            $model = StrictJsonDecoder::decodeObject($bytes);
            $typed = json_decode($bytes, false, 8, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            if (!$typed instanceof \stdClass || !property_exists($typed, 'profiles') || !is_array($typed->profiles)) {
                throw InvalidStorageApplicationException::invalid('invalid_profile');
            }
            $this->keys($model, ['schema', 'profiles']);
            if ('kongono/storage-application-profile-source/1' !== $model['schema']) {
                throw InvalidStorageApplicationException::invalid('invalid_schema');
            }
            $rows = $model['profiles'];
            if (!is_array($rows) || !array_is_list($rows) || count($rows) < 1 || count($rows) > 256) {
                throw InvalidStorageApplicationException::invalid('invalid_profile');
            }
            $profiles = [];
            $names = [];
            foreach ($rows as $position => $value) {
                $typedRow = $typed->profiles[$position] ?? null;
                if (!$typedRow instanceof \stdClass || !property_exists($typedRow, 'limits') || !$typedRow->limits instanceof \stdClass
                    || !property_exists($typedRow, 'capabilities') || !is_array($typedRow->capabilities)) {
                    throw InvalidStorageApplicationException::invalid('invalid_profile');
                }
                $row = $this->object($value);
                $this->keys($row, ['name', 'contract_version', 'namespace', 'capabilities', 'digest_algorithm', 'limits']);
                $limits = $this->object($row['limits']);
                $this->keys($limits, ['max_chunk_bytes', 'max_object_bytes', 'max_list_page_size']);
                if (!is_string($row['name']) || 1 !== $row['contract_version'] || !is_string($row['namespace'])
                    || !is_array($row['capabilities']) || !array_is_list($row['capabilities'])
                    || !(null === $row['digest_algorithm'] || is_string($row['digest_algorithm']))
                    || !is_int($limits['max_chunk_bytes']) || !is_int($limits['max_object_bytes']) || !is_int($limits['max_list_page_size'])) {
                    throw InvalidStorageApplicationException::invalid('invalid_profile');
                }
                if (isset($names[$row['name']])) {
                    throw InvalidStorageApplicationException::invalid('duplicate_profile');
                }
                $names[$row['name']] = true;
                $capabilities = [];
                $seen = [];
                foreach ($row['capabilities'] as $item) {
                    if (!is_string($item) || isset($seen[$item]) || null === ($capability = StorageCapability::tryFrom($item))) {
                        throw InvalidStorageApplicationException::invalid('invalid_capability');
                    }
                    $seen[$item] = true;
                    $capabilities[] = $capability;
                }
                $digest = null === $row['digest_algorithm'] ? null : StorageDigestAlgorithm::tryFrom($row['digest_algorithm']);
                if (null !== $row['digest_algorithm'] && null === $digest) {
                    throw InvalidStorageApplicationException::invalid('invalid_profile');
                }
                $profiles[] = new StorageProfileDeclaration(
                    ContractVersion::Version1,
                    new StorageProfileName($row['name']),
                    new StorageNamespace($row['namespace']),
                    new StorageCapabilitySet($capabilities),
                    $digest,
                    new StorageProfileLimits($limits['max_chunk_bytes'], $limits['max_object_bytes'], $limits['max_list_page_size']),
                );
            }
            return new StorageProfileSet($profiles);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidStorageApplicationException) {
                throw $failure;
            }
            throw InvalidStorageApplicationException::invalid('invalid_json', $failure);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidStorageApplicationException::invalid('invalid_schema');
        }
        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidStorageApplicationException::invalid('invalid_schema');
            }
            $object[$key] = $entry;
        }
        return $object;
    }

    /**
     * @param array<array-key, mixed> $object
     * @param list<string> $expected
     */
    private function keys(array $object, array $expected): void
    {
        $actual = [];
        foreach ($object as $key => $_) {
            if (!is_string($key)) {
                throw InvalidStorageApplicationException::invalid('invalid_schema');
            }
            $actual[] = $key;
        }
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            throw InvalidStorageApplicationException::invalid('invalid_schema');
        }
    }
}
