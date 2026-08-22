<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;

/** Projects a deterministic bounded request-header set from validated CGI fields. */
#[Api(ApiStatus::Internal)]
final class CgiHeaderProjector
{
    private function __construct() {}

    /**
     * @param array<string, string> $server
     * @return array<string, list<string>>
     */
    public static function project(array $server): array
    {
        $httpKeys = [];
        foreach (array_keys($server) as $key) {
            if (str_starts_with($key, 'HTTP_') && 'HTTP_HOST' !== $key) {
                $httpKeys[] = $key;
            }
        }
        usort($httpKeys, static fn(string $left, string $right): int => strcmp($left, $right));

        if (array_key_exists('HTTP_CONTENT_TYPE', $server)) {
            self::reject('content_type_ambiguous');
        }
        if (array_key_exists('HTTP_CONTENT_LENGTH', $server)) {
            self::reject('content_length_ambiguous');
        }
        if (array_key_exists('HTTP_PROXY', $server)) {
            self::reject('proxy_header_forbidden');
        }
        if (array_key_exists('HTTP_TRANSFER_ENCODING', $server)) {
            self::reject('transfer_encoding_forbidden');
        }

        $projected = [['Host', $server['HTTP_HOST']]];
        if (isset($server['CONTENT_TYPE']) && '' !== $server['CONTENT_TYPE']) {
            $projected[] = ['Content-Type', $server['CONTENT_TYPE']];
        }
        if (isset($server['CONTENT_LENGTH']) && '' !== $server['CONTENT_LENGTH']) {
            $projected[] = ['Content-Length', $server['CONTENT_LENGTH']];
        }
        foreach ($httpKeys as $key) {
            $projected[] = [self::fieldName($key), $server[$key]];
        }

        foreach ($projected as [$name]) {
            if (1 !== preg_match("/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/D", $name)) {
                self::reject('header_name_invalid');
            }
        }
        foreach ($projected as [, $value]) {
            if (self::containsUnsafeByte($value)) {
                self::reject('header_value_invalid');
            }
        }

        $seen = [];
        foreach ($projected as [$name]) {
            $normalized = strtolower($name);
            if (isset($seen[$normalized])) {
                self::reject('header_name_duplicate');
            }
            $seen[$normalized] = true;
        }
        if (count($projected) > 128) {
            self::reject('header_count_exceeded');
        }
        foreach ($projected as [, $value]) {
            if (strlen($value) > 8192) {
                self::reject('header_value_too_long');
            }
        }

        $totalBytes = 0;
        $result = [];
        foreach ($projected as [$name, $value]) {
            $totalBytes += strlen($name) + strlen($value);
            $result[$name] = [$value];
        }
        if ($totalBytes > 65536) {
            self::reject('header_bytes_exceeded');
        }

        return $result;
    }

    private static function fieldName(string $key): string
    {
        $segments = explode('_', strtolower(substr($key, 5)));

        return implode('-', array_map(static fn(string $segment): string => ucfirst($segment), $segments));
    }

    private static function containsUnsafeByte(string $value): bool
    {
        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            $byte = ord($value[$position]);
            if (0 === $byte || 10 === $byte || 13 === $byte || 127 === $byte || ($byte < 32 && 9 !== $byte)) {
                return true;
            }
        }

        return false;
    }

    private static function reject(string $violation): never
    {
        throw new InvalidCgiRequestException(PhpCgiDiagnostic::InvalidRequestHeaders, $violation);
    }
}
