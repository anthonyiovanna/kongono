<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\ResponsesResponseDecodingException;

/** Applies mapping-owned JSON member and closed-object checks without retaining input. */
#[Api(ApiStatus::Internal)]
final class ProtocolGuard
{
    public static function assertNoDuplicateMembers(string $json): void
    {
        $position = 0;
        self::skipWhitespace($json, $position);
        self::scanValue($json, $position);
    }

    /** @param array<string, mixed> $object
     * @param list<string> $allowed
     */
    public static function assertExactKeys(array $object, array $allowed, string $field): void
    {
        $keys = array_keys($object);
        sort($keys, SORT_STRING);
        $expected = $allowed;
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw ResponsesResponseDecodingException::unsupportedMapping('object_members', $field);
        }
    }

    private static function scanValue(string $json, int &$position): void
    {
        self::skipWhitespace($json, $position);
        $byte = $json[$position] ?? '';
        if ('{' === $byte) {
            self::scanObject($json, $position);

            return;
        }
        if ('[' === $byte) {
            self::scanArray($json, $position);

            return;
        }
        if ('"' === $byte) {
            self::scanString($json, $position);

            return;
        }
        while (isset($json[$position]) && !str_contains(",]} \t\r\n", $json[$position])) {
            ++$position;
        }
    }

    private static function scanObject(string $json, int &$position): void
    {
        ++$position;
        self::skipWhitespace($json, $position);
        $seen = [];
        if ('}' === ($json[$position] ?? '')) {
            ++$position;

            return;
        }
        while (true) {
            $literal = self::scanString($json, $position);
            try {
                $key = json_decode($literal, true, 2, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw ResponsesResponseDecodingException::invalidSyntax('json_syntax');
            }
            if (!is_string($key)) {
                throw ResponsesResponseDecodingException::invalidSyntax('json_syntax');
            }
            $identity = base64_encode($key);
            if (isset($seen[$identity])) {
                throw ResponsesResponseDecodingException::invalidSyntax('duplicate_member');
            }
            $seen[$identity] = true;
            self::skipWhitespace($json, $position);
            ++$position;
            self::scanValue($json, $position);
            self::skipWhitespace($json, $position);
            if ('}' === ($json[$position] ?? '')) {
                ++$position;

                return;
            }
            ++$position;
            self::skipWhitespace($json, $position);
        }
    }

    private static function scanArray(string $json, int &$position): void
    {
        ++$position;
        self::skipWhitespace($json, $position);
        if (']' === ($json[$position] ?? '')) {
            ++$position;

            return;
        }
        while (true) {
            self::scanValue($json, $position);
            self::skipWhitespace($json, $position);
            if (']' === ($json[$position] ?? '')) {
                ++$position;

                return;
            }
            ++$position;
        }
    }

    private static function scanString(string $json, int &$position): string
    {
        $start = $position++;
        while (isset($json[$position])) {
            if ('\\' === $json[$position]) {
                $position += 2;

                continue;
            }
            if ('"' === $json[$position]) {
                ++$position;

                return substr($json, $start, $position - $start);
            }
            ++$position;
        }

        throw ResponsesResponseDecodingException::invalidSyntax('json_syntax');
    }

    private static function skipWhitespace(string $json, int &$position): void
    {
        while (isset($json[$position]) && str_contains(" \t\r\n", $json[$position])) {
            ++$position;
        }
    }
}
