<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Exception\InvalidViteToolchainException;

/** Centralizes exact Vite-adapter scalar validation. */
#[Api(ApiStatus::Internal)]final class ViteAdapterGuard
{
    public static function version(string $value): string
    {
        if (strlen($value) < 1 || strlen($value) > 64 || 1 !== preg_match('/\A(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?\z/D', $value)) {
            throw InvalidViteToolchainException::invalid('version_grammar');
        }return$value;
    }public static function sha256(string $value): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidViteToolchainException::invalid('hash_grammar');
        }return$value;
    }
    private function __construct() {}
}
