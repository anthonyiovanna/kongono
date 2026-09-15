<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionEntry;
use Kongono\AssetResolution\ContractVersion;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;

/** Centralizes exact bounded canonical validation without ambient input. */
#[Api(ApiStatus::Internal)]
final class ResolutionGuard
{
    public const int MAX_CONTENT_BYTES = 67_108_864;

    public static function sha256(string $value, string $violation): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAssetResolutionException::invalidArtifact($violation);
        }

        return $value;
    }

    public static function objectKeyGrammar(string $value): string
    {
        if (1 !== preg_match('/\Aobjects\/sha256\/([a-f0-9]{2})\/([a-f0-9]{64})\z/D', $value, $match) || $match[1] !== substr($match[2], 0, 2)) {
            throw InvalidAssetResolutionException::invalidArtifact('object_key_grammar');
        }

        return $value;
    }

    public static function objectKey(string $value, ContentIdentity $identity): string
    {
        self::objectKeyGrammar($value);
        if (!str_ends_with($value, '/' . $identity->value())) {
            throw InvalidAssetResolutionException::invalidArtifact('object_key_content_relation');
        }

        return $value;
    }

    public static function publicBaseUrl(string $value): string
    {
        if (strlen($value) < 1 || strlen($value) > 2048 || !str_ends_with($value, '/') || str_contains($value, '\\') || str_contains($value, '?') || str_contains($value, '#') || 1 === preg_match('/[^\x21-\x7E]/', $value) || str_starts_with($value, '//')) {
            throw InvalidAssetResolutionException::invalidPolicy('public_base_url_grammar');
        }
        $path = $value;
        if (!str_starts_with($value, '/')) {
            if (1 !== preg_match('/\Ahttps?:\/\//D', $value)) {
                throw InvalidAssetResolutionException::invalidPolicy('public_base_url_scheme');
            }
            $parts = parse_url($value);
            if (!is_array($parts) || !isset($parts['scheme'], $parts['host'], $parts['path']) || '' === $parts['host'] || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || strtolower($parts['scheme']) !== $parts['scheme'] || strtolower($parts['host']) !== $parts['host']) {
                throw InvalidAssetResolutionException::invalidPolicy('public_base_url_authority');
            }
            if (isset($parts['port']) && $parts['port'] < 1) {
                throw InvalidAssetResolutionException::invalidPolicy('public_base_url_port');
            }
            $path = $parts['path'];
        }
        foreach ('' === trim($path, '/') ? [] : explode('/', trim($path, '/')) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || 1 === preg_match('/%(?:2[eE]|2[fF]|5[cC]|25)/', $segment) || preg_match_all('/%[0-9A-F]{2}/', $segment) !== substr_count($segment, '%')) {
                throw InvalidAssetResolutionException::invalidPolicy('public_base_url_path');
            }
        }

        return $value;
    }

    /** @param list<AssetResolutionEntry> $entries */
    public static function canonicalArtifactBytes(ContractVersion $version, string $producerKind, string $buildIdentity, string $sourceSetIdentity, string $graphIdentity, array $entries): string
    {
        $model = [];
        foreach ($entries as $entry) {
            $dependencies = [];
            foreach ($entry->dependencies() as $dependency) {
                $dependencies[] = ['kind' => $dependency['kind']->value, 'object' => self::objectModel($dependency['object'])];
            }
            $model[] = ['identity' => $entry->identity()->value(), 'primary' => self::objectModel($entry->primary()), 'dependencies' => $dependencies];
        }
        try {
            return json_encode([
                'schema' => 'kongono.asset-resolution-artifact',
                'artifact_version' => 1,
                'contract_version' => $version->value,
                'producer_kind' => $producerKind,
                'build_sha256' => $buildIdentity,
                'source_set_sha256' => $sourceSetIdentity,
                'graph_sha256' => $graphIdentity,
                'entries' => $model,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        } catch (JsonException $failure) {
            throw InvalidAssetResolutionException::invalidArtifact('canonical_encoding', previous: $failure);
        }
    }

    /** @return array<string, int|string|array<string, string>> */
    private static function objectModel(\Kongono\AssetResolution\Artifact\AssetResolutionObject $object): array
    {
        return [
            'object_key' => $object->objectKey(),
            'kind' => $object->kind()->value,
            'content_type' => $object->contentType()->value,
            'content_sha256' => $object->contentIdentity()->value(),
            'byte_count' => $object->byteCount(),
            'integrity' => ['algorithm' => $object->integrity()->algorithm()->value, 'digest' => $object->integrity()->digest()],
            'preload_hint' => $object->preloadHint()->value,
        ];
    }

    private function __construct() {}
}
