<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;

/** Centralizes exact canonical model and structural guards without ambient input. */
#[Api(ApiStatus::Internal)]
final class BuildGuard
{
    /** @return list<array{identity:string, kind:string, content_type:string}> */
    public static function declarations(AssetDeclarationSet $set): array
    {
        return array_map(static fn(AssetDeclaration $value): array => [
            'identity' => $value->identity()->value(),
            'kind' => $value->kind()->value,
            'content_type' => $value->contentType()->value,
        ], $set->declarations());
    }

    /** @return list<array{source:string, target:string, kind:string}> */
    public static function dependencies(AssetDeclarationSet $set): array
    {
        return array_map(static fn(AssetDependency $value): array => [
            'source' => $value->source()->value(),
            'target' => $value->target()->value(),
            'kind' => $value->kind()->value,
        ], $set->dependencies());
    }

    /** @return list<array{identity:string}> */
    public static function entryPoints(AssetDeclarationSet $set): array
    {
        return array_map(static fn(AssetEntryPoint $value): array => ['identity' => $value->identity()->value()], $set->entryPoints());
    }

    /**
     * @param array<array-key, mixed> $value
     * @return array<string, string>
     */
    public static function exactStringMap(array $value): array
    {
        if ([] !== $value && array_is_list($value)) {
            throw InvalidAssetBuildArtifactException::invalid('output_map_shape');
        }
        $copy = [];
        foreach ($value as $path => $bytes) {
            if (!is_string($path) || !is_string($bytes)) {
                throw InvalidAssetBuildArtifactException::invalid('output_map_shape');
            }
            $copy[$path] = $bytes;
        }
        ksort($copy, SORT_STRING);

        return $copy;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    public static function exactKeys(array $value, array $keys): void
    {
        if (array_keys($value) !== $keys) {
            throw InvalidAssetBuildArtifactException::invalid('artifact_schema');
        }
    }

    public static function lowercaseSha256(string $value): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAssetBuildArtifactException::invalid('identity_grammar');
        }

        return $value;
    }

    private function __construct() {}
}
