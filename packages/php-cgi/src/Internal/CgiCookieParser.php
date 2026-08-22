<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;

/** Parses only the bounded flat Cookie grammar selected for the CGI profile. */
#[Api(ApiStatus::Internal)]
final class CgiCookieParser
{
    private function __construct() {}

    /** @return array<string, string> */
    public static function parse(?string $cookie): array
    {
        if (null === $cookie || '' === $cookie) {
            return [];
        }
        if (strlen($cookie) > 4096) {
            self::reject('raw_cookie_too_long');
        }

        $pairs = explode(';', $cookie);
        foreach ($pairs as $position => $pair) {
            if ($position > 0 && str_starts_with($pair, ' ')) {
                $pairs[$position] = substr($pair, 1);
            }
            if ('' === $pairs[$position]) {
                self::reject('cookie_pair_empty');
            }
        }
        if (count($pairs) > 64) {
            self::reject('cookie_pair_count_exceeded');
        }

        $result = [];
        foreach ($pairs as $pair) {
            $separator = strpos($pair, '=');
            if (false === $separator) {
                self::reject('cookie_pair_missing_equals');
            }
            $name = substr($pair, 0, $separator);
            $value = substr($pair, $separator + 1);
            if ('' === $name) {
                self::reject('cookie_name_empty');
            }
            if (strlen($name) > 128) {
                self::reject('cookie_name_too_long');
            }
            if (1 !== preg_match("/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/D", $name)) {
                self::reject('cookie_name_invalid');
            }
            if (strlen($value) > 1024) {
                self::reject('cookie_value_too_long');
            }
            if (1 === preg_match('/[^\x20-\x7E]|[;,\\\\"]/D', $value)) {
                self::reject('cookie_value_invalid');
            }
            if (array_key_exists($name, $result)) {
                self::reject('cookie_name_duplicate');
            }
            $result[$name] = $value;
        }

        return $result;
    }

    private static function reject(string $violation): never
    {
        throw new InvalidCgiRequestException(PhpCgiDiagnostic::InvalidCookieParameters, $violation);
    }
}
