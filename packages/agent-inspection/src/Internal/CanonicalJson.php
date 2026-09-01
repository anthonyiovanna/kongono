<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Internal;

use JsonException;
use Kongono\AgentInspection\Exception\InvalidAgentInspectionException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Encodes only package-owned ordered language-neutral evidence. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidAgentInspectionException::invalid('serialization', $failure);
        }
    }

    private function __construct() {}
}
