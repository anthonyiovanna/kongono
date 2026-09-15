<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Strictly admits one bounded duplicate-free JSON object. */
#[Api(ApiStatus::Internal)]
final class StrictJsonDecoder
{
    /** @return array<string, mixed> */
    public static function decodeObject(string $json, int $maximumDepth = 32): array
    {
        if ($maximumDepth < 1 || $maximumDepth > 64) {
            throw new JsonException('JSON depth is outside the fixed bound.');
        }
        $position = 0;
        self::skipWhitespace($json, $position);
        self::value($json, $position, 1, $maximumDepth);
        self::skipWhitespace($json, $position);
        if ($position !== strlen($json)) {
            throw new JsonException('JSON has a trailing token.');
        }
        $decoded = json_decode($json, true, $maximumDepth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('JSON root must be an object.');
        }

        $object = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new JsonException('JSON object key must be a string.');
            }
            $object[$key] = $value;
        }

        return $object;
    }

    private static function value(string $json, int &$position, int $depth, int $maximumDepth): void
    {
        if ($depth > $maximumDepth || $position >= strlen($json)) {
            throw new JsonException('JSON structure exceeds its bound.');
        }
        match ($json[$position]) {
            '{' => self::object($json, $position, $depth, $maximumDepth),
            '[' => self::array($json, $position, $depth, $maximumDepth),
            '"' => self::string($json, $position),
            't' => self::literal($json, $position, 'true'),
            'f' => self::literal($json, $position, 'false'),
            'n' => self::literal($json, $position, 'null'),
            default => self::integer($json, $position),
        };
    }

    private static function object(string $json, int &$position, int $depth, int $maximumDepth): void
    {
        ++$position;
        self::skipWhitespace($json, $position);
        if (($json[$position] ?? null) === '}') {
            ++$position;
            return;
        }
        $keys = [];
        while (true) {
            if (($json[$position] ?? null) !== '"') {
                throw new JsonException('JSON object key is invalid.');
            }
            $key = self::string($json, $position);
            if (array_key_exists($key, $keys)) {
                throw new JsonException('JSON object contains a duplicate member.');
            }
            $keys[$key] = true;
            self::skipWhitespace($json, $position);
            if (($json[$position] ?? null) !== ':') {
                throw new JsonException('JSON object separator is invalid.');
            }
            ++$position;
            self::skipWhitespace($json, $position);
            self::value($json, $position, $depth + 1, $maximumDepth);
            self::skipWhitespace($json, $position);
            $next = $json[$position] ?? null;
            if ('}' === $next) {
                ++$position;
                return;
            }
            if (',' !== $next) {
                throw new JsonException('JSON object delimiter is invalid.');
            }
            ++$position;
            self::skipWhitespace($json, $position);
        }
    }

    private static function array(string $json, int &$position, int $depth, int $maximumDepth): void
    {
        ++$position;
        self::skipWhitespace($json, $position);
        if (($json[$position] ?? null) === ']') {
            ++$position;
            return;
        }
        while (true) {
            self::value($json, $position, $depth + 1, $maximumDepth);
            self::skipWhitespace($json, $position);
            $next = $json[$position] ?? null;
            if (']' === $next) {
                ++$position;
                return;
            }
            if (',' !== $next) {
                throw new JsonException('JSON array delimiter is invalid.');
            }
            ++$position;
            self::skipWhitespace($json, $position);
        }
    }

    private static function string(string $json, int &$position): string
    {
        $start = $position++;
        $escaped = false;
        while ($position < strlen($json)) {
            $byte = ord($json[$position]);
            if (!$escaped && 0x22 === $byte) {
                ++$position;
                $decoded = json_decode(substr($json, $start, $position - $start), true, 2, JSON_THROW_ON_ERROR);
                if (!is_string($decoded)) {
                    throw new JsonException('JSON string is invalid.');
                }

                return $decoded;
            }
            if (!$escaped && $byte < 0x20) {
                throw new JsonException('JSON string contains a control byte.');
            }
            if (!$escaped && 0x5C === $byte) {
                $escaped = true;
                ++$position;
                continue;
            }
            $escaped = false;
            ++$position;
        }

        throw new JsonException('JSON string is unterminated.');
    }

    private static function literal(string $json, int &$position, string $literal): void
    {
        if (substr($json, $position, strlen($literal)) !== $literal) {
            throw new JsonException('JSON literal is invalid.');
        }
        $position += strlen($literal);
    }

    private static function integer(string $json, int &$position): void
    {
        if (1 !== preg_match('/\A-?(?:0|[1-9][0-9]*)/', substr($json, $position), $match)) {
            throw new JsonException('JSON number is invalid.');
        }
        $position += strlen($match[0]);
        if (isset($json[$position]) && in_array($json[$position], ['.', 'e', 'E'], true)) {
            throw new JsonException('JSON floating-point numbers are unsupported.');
        }
    }

    private static function skipWhitespace(string $json, int &$position): void
    {
        while (isset($json[$position]) && in_array($json[$position], [" ", "\t", "\n"], true)) {
            ++$position;
        }
    }

    private function __construct() {}
}
