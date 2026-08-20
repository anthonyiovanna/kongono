<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;

/** Centralizes bounded syntax validation shared by HTTP value objects. */
#[Api(ApiStatus::Internal)]
final class HttpMessageValidation
{
    private const TOKEN_PATTERN = "/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/D";

    public static function protocolVersion(string $version): string
    {
        if (!in_array($version, ['1.0', '1.1', '2', '2.0', '3', '3.0'], true)) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidProtocolVersion, ['actual_length' => strlen($version)]);
        }
        return $version;
    }

    public static function token(string $value, HttpMessageDiagnostic $diagnostic): string
    {
        if (1 !== preg_match(self::TOKEN_PATTERN, $value)) {
            $field = HttpMessageDiagnostic::InvalidHeaderName === $diagnostic ? 'name_length' : 'actual_length';
            throw new InvalidMessageArgumentException($diagnostic, [$field => strlen($value)]);
        }
        return $value;
    }

    public static function isToken(string $value): bool
    {
        return 1 === preg_match(self::TOKEN_PATTERN, $value);
    }

    /** @return list<string> */
    public static function headerValues(mixed $value): array
    {
        if (is_string($value)) {
            $values = [$value];
        } elseif (is_array($value)) {
            if (!array_is_list($value)) {
                $kind = 'unknown';
                $expected = 0;
                foreach (array_keys($value) as $key) {
                    if ($key !== $expected) {
                        $kind = get_debug_type($key);
                        break;
                    }
                    ++$expected;
                }
                throw new InvalidMessageArgumentException(HttpMessageDiagnostic::HeaderValuesNotList, ['entry_count' => count($value), 'first_unexpected_key_kind' => $kind]);
            }
            $values = $value;
        } else {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::HeaderValueNotString, ['value_position' => 0, 'actual_value_kind' => self::valueKind($value)]);
        }

        foreach ($values as $position => $entry) {
            if (!is_string($entry)) {
                throw new InvalidMessageArgumentException(HttpMessageDiagnostic::HeaderValueNotString, ['value_position' => $position, 'actual_value_kind' => self::valueKind($entry)]);
            }
            $violation = self::unsafeTextViolation($entry, true);
            if (null !== $violation) {
                throw new InvalidMessageArgumentException(HttpMessageDiagnostic::UnsafeHeaderValue, ['value_position' => $position, 'violation' => $violation]);
            }
        }
        return $values;
    }

    public static function safeReasonPhrase(string $value): string
    {
        $violation = self::unsafeTextViolation($value, true);
        if (null !== $violation) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidReasonPhrase, ['actual_length' => strlen($value), 'violation' => $violation]);
        }
        return $value;
    }

    public static function valueKind(mixed $value): string
    {
        return substr(get_debug_type($value), 0, 64);
    }

    private static function unsafeTextViolation(string $value, bool $allowTab): ?string
    {
        for ($i = 0, $length = strlen($value); $i < $length; ++$i) {
            $byte = ord($value[$i]);
            if (0 === $byte) {
                return 'nul';
            }
            if (13 === $byte) {
                return 'carriage_return';
            }
            if (10 === $byte) {
                return 'line_feed';
            }
            if (127 === $byte) {
                return 'del';
            }
            if ($byte < 32 && (!$allowTab || 9 !== $byte)) {
                return 'control';
            }
        }
        return null;
    }
}
