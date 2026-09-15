<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAdapterBuildArtifactException;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;

/** Centralizes exact adapter-contract validation without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class AdapterContractGuard
{
    public static function profile(string $value): string
    {
        if (strlen($value) < 1 || strlen($value) > 96 || 1 !== preg_match('/\A[a-z][a-z0-9-]*(?:\/[a-z0-9][a-z0-9.-]*)+\z/D', $value)) {
            throw InvalidAssetAdapterContractException::request('adapter_profile_grammar');
        }

        return $value;
    }

    public static function sha256(string $value, string $violation = 'sha256_grammar'): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAssetAdapterContractException::request($violation);
        }

        return $value;
    }

    /** @param list<string> $allowed */
    public static function token(string $value, array $allowed, string $fallback = 'unsupported_violation'): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<string, string>
     */
    public static function exactStringMap(array $value): array
    {
        $result = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key) || !is_string($entry) || array_key_exists($key, $result)) {
                throw InvalidAdapterBuildArtifactException::invalid('output_map_shape');
            }
            $result[$key] = $entry;
        }
        ksort($result, SORT_STRING);

        return $result;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string>         $keys
     */
    public static function exactKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            throw InvalidAdapterBuildArtifactException::invalid('artifact_schema');
        }
    }

    private function __construct() {}
}
