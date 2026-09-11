<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Encodes already ordered mapping values as compact deterministic JSON. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    /** @param array<string, mixed> $value
     * @throws JsonException
     */
    public static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
