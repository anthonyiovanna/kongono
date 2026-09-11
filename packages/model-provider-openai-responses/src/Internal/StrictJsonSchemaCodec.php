<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException;
use stdClass;

/** Decodes and canonicalizes the closed version-2 schema subset without I/O. */
#[Api(ApiStatus::Internal)]
final class StrictJsonSchemaCodec
{
    public const int MAXIMUM_SOURCE_BYTES = 65536;
    public const int MAXIMUM_JSON_DEPTH = 16;

    /** @return array{canonical: string, structure: array<string, mixed>} */
    public function decode(string $json): array
    {
        if (strlen($json) > self::MAXIMUM_SOURCE_BYTES) {
            throw InvalidStrictJsonSchemaException::violation('source_bytes', 'schema', self::MAXIMUM_SOURCE_BYTES);
        }
        if (1 !== preg_match('//u', $json)) {
            throw InvalidStrictJsonSchemaException::violation('utf8', 'schema');
        }
        try {
            $decoded = json_decode($json, false, self::MAXIMUM_JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw InvalidStrictJsonSchemaException::violation(JSON_ERROR_DEPTH === $failure->getCode() ? 'json_depth' : 'json_syntax', 'schema');
        }
        if (!$decoded instanceof stdClass) {
            throw InvalidStrictJsonSchemaException::violation('root_object', 'schema');
        }
        self::assertNoDuplicateMembers($json);

        $structure = (new StrictJsonSchemaValidator())->validateDefinition($decoded);
        $canonicalValue = self::canonicalize($decoded);
        try {
            $canonical = json_encode($canonicalValue, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw InvalidStrictJsonSchemaException::violation('json_syntax', 'schema');
        }

        return ['canonical' => $canonical, 'structure' => $structure];
    }

    public static function canonicalize(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $members = get_object_vars($value);
            ksort($members, SORT_STRING);
            $copy = new stdClass();
            foreach ($members as $key => $member) {
                $copy->{$key} = self::canonicalize($member);
            }

            return $copy;
        }
        if (is_array($value)) {
            return array_map(self::canonicalize(...), $value);
        }

        return $value;
    }

    private static function assertNoDuplicateMembers(string $json): void
    {
        $position = 0;
        self::skipWhitespace($json, $position);
        self::scanValue($json, $position);
    }

    private static function scanValue(string $json, int &$position): void
    {
        self::skipWhitespace($json, $position);
        $byte = $json[$position] ?? '';
        if ('{' === $byte) {
            self::scanObject($json, $position);
        } elseif ('[' === $byte) {
            self::scanArray($json, $position);
        } elseif ('"' === $byte) {
            self::scanString($json, $position);
        } else {
            while (isset($json[$position]) && !str_contains(",]} \t\r\n", $json[$position])) {
                ++$position;
            }
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
                throw InvalidStrictJsonSchemaException::violation('json_syntax', 'schema');
            }
            if (!is_string($key)) {
                throw InvalidStrictJsonSchemaException::violation('json_syntax', 'schema');
            }
            $identity = base64_encode($key);
            if (isset($seen[$identity])) {
                throw InvalidStrictJsonSchemaException::violation('duplicate_member', 'schema');
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
            } elseif ('"' === $json[$position]) {
                ++$position;

                return substr($json, $start, $position - $start);
            } else {
                ++$position;
            }
        }

        throw InvalidStrictJsonSchemaException::violation('json_syntax', 'schema');
    }

    private static function skipWhitespace(string $json, int &$position): void
    {
        while (isset($json[$position]) && str_contains(" \t\r\n", $json[$position])) {
            ++$position;
        }
    }
}
