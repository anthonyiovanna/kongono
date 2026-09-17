<?php

declare(strict_types=1);

namespace CacheMinimalApp;

use JsonException;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\Value\CacheValueBytes;

/** Application-owned deterministic codec for one tiny closed value schema. */
final class ApplicationJsonCodec
{
    public function identity(): CacheCodecIdentity
    {
        return new CacheCodecIdentity('application-json', 1);
    }

    /** @param array{message:string} $value */
    public function encode(array $value): CacheValueBytes
    {
        if ('' === $value['message'] || strlen($value['message']) > 64 || 1 !== preg_match('//u', $value['message'])) {
            throw new JsonException('Application value is invalid.');
        }
        return new CacheValueBytes(json_encode(['message' => $value['message']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @return array{message:string} */
    public function decode(CacheValueBytes $bytes): array
    {
        $value = json_decode($bytes->bytes(), true, 4, JSON_THROW_ON_ERROR);
        if (!is_array($value) || array_keys($value) !== ['message'] || !is_string($value['message']) || !$this->encode(['message' => $value['message']])->equals($bytes)) {
            throw new JsonException('Cached application value is invalid.');
        }
        return ['message' => $value['message']];
    }
}
