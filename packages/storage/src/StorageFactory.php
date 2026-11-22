<?php

declare(strict_types=1);

namespace Kongono\Storage;

use Kongono\Core\Config;

/**
 * Zero-boilerplate horizontal-scaling storage factory:
 * automatically returns `S3Storage` when `S3_BUCKET`, `S3_ACCESS_KEY_ID`, and `S3_SECRET_ACCESS_KEY`
 * are configured (supporting AWS S3, Cloudflare R2, MinIO, DigitalOcean Spaces), falling back to `LocalStorage`.
 */
final readonly class StorageFactory
{
    public static function fromEnv(string $fallbackDirectory): StorageInterface
    {
        $bucket = Config::env('S3_BUCKET');
        $key = Config::env('S3_ACCESS_KEY_ID');
        $secret = Config::env('S3_SECRET_ACCESS_KEY');

        if (
            null !== $bucket && '' !== trim($bucket)
            && null !== $key && '' !== trim($key)
            && null !== $secret && '' !== trim($secret)
        ) {
            $region = Config::env('S3_REGION') ?? 'us-east-1';
            $endpoint = Config::env('S3_ENDPOINT');

            return new S3Storage(
                bucket: trim($bucket),
                accessKeyId: trim($key),
                secretAccessKey: $secret,
                region: trim($region),
                endpoint: (null !== $endpoint && '' !== trim($endpoint)) ? trim($endpoint) : null,
            );
        }

        return new LocalStorage($fallbackDirectory);
    }
}
