<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;

/** Parses only the bounded flat query grammar selected for the CGI profile. */
#[Api(ApiStatus::Internal)]
final class CgiQueryParser
{
    private function __construct() {}

    /** @return array<string, string> */
    public static function parse(string $query): array
    {
        if ('' === $query) {
            return [];
        }

        $fields = explode('&', $query);
        foreach ($fields as $field) {
            if ('' === $field) {
                self::reject('query_field_empty');
            }
        }
        if (count($fields) > 128) {
            self::reject('query_pair_count_exceeded');
        }

        $result = [];
        foreach ($fields as $field) {
            $separator = strpos($field, '=');
            $encodedName = false === $separator ? $field : substr($field, 0, $separator);
            $encodedValue = false === $separator ? '' : substr($field, $separator + 1);
            $name = self::decode($encodedName);
            $value = self::decode($encodedValue);

            if ('' === $name) {
                self::reject('query_key_empty');
            }
            if (strlen($name) > 128) {
                self::reject('query_key_too_long');
            }
            if (1 !== preg_match('//u', $name)) {
                self::reject('query_key_utf8_invalid');
            }
            if (self::containsUnsafeByte($name)) {
                self::reject('query_key_unsafe');
            }
            if (str_contains($name, '[') || str_contains($name, ']')) {
                self::reject('query_nesting_forbidden');
            }
            if (strlen($value) > 4096) {
                self::reject('query_value_too_long');
            }
            if (1 !== preg_match('//u', $value)) {
                self::reject('query_value_utf8_invalid');
            }
            if (self::containsUnsafeByte($value)) {
                self::reject('query_value_unsafe');
            }
            if (array_key_exists($name, $result)) {
                self::reject('query_key_duplicate');
            }

            $result[$name] = $value;
        }

        return $result;
    }

    private static function decode(string $value): string
    {
        $decoded = '';
        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            if ('+' === $value[$position]) {
                $decoded .= ' ';
                continue;
            }
            if ('%' !== $value[$position]) {
                $decoded .= $value[$position];
                continue;
            }
            if ($position + 2 >= $length || !ctype_xdigit($value[$position + 1] . $value[$position + 2])) {
                self::reject('query_percent_invalid');
            }
            $byte = (int) hexdec(substr($value, $position + 1, 2));
            if ($byte < 0 || $byte > 255) {
                self::reject('query_percent_invalid');
            }
            $decoded .= chr($byte);
            $position += 2;
        }

        return $decoded;
    }

    private static function containsUnsafeByte(string $value): bool
    {
        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            $byte = ord($value[$position]);
            if ($byte < 32 || 127 === $byte) {
                return true;
            }
        }

        return false;
    }

    private static function reject(string $violation): never
    {
        throw new InvalidCgiRequestException(PhpCgiDiagnostic::InvalidQueryParameters, $violation);
    }
}
