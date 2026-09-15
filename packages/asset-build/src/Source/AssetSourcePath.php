<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetSourceException;

/** Carries one protected application-owned relative source-path identity. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetSourcePath
{
    private string $value;

    public function __construct(string $value)
    {
        if ('' === $value || strlen($value) > 512 || 1 !== preg_match('/\A[\x20-\x7e]+\z/D', $value) || str_contains($value, '\\') || str_contains($value, ':') || str_starts_with($value, '/') || strtolower($value) !== $value) {
            throw InvalidAssetSourceException::invalid('source_path_grammar');
        }
        $segments = explode('/', $value);
        if (count($segments) > 64) {
            throw InvalidAssetSourceException::invalid('source_path_grammar');
        }
        foreach ($segments as $segment) {
            $base = strtolower(explode('.', $segment, 2)[0]);
            if (1 !== preg_match('/\A[a-z0-9][a-z0-9._-]{0,127}\z/D', $segment) || '.' === $segment || '..' === $segment || str_ends_with($segment, '.') || str_ends_with($segment, ' ') || in_array($base, ['con', 'prn', 'aux', 'nul', 'clock$', 'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9', 'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9'], true)) {
                throw InvalidAssetSourceException::invalid('source_path_grammar');
            }
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
