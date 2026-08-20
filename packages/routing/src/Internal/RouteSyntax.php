<?php

declare(strict_types=1);

namespace Kongono\Routing\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use Kongono\Routing\Exception\InvalidRouteRequestException;

/** Centralizes the exact byte-oriented Phase 2D route and request grammar. */
#[Api(ApiStatus::Internal)]
final class RouteSyntax
{
    private function __construct() {}

    public static function routeName(string $name): string
    {
        $length = strlen($name);
        if (0 === $length) {
            self::invalidName($length, 'empty');
        }
        if ($length > 128) {
            self::invalidName($length, 'too_long');
        }
        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9._-]*$/D', $name)) {
            self::invalidName($length, 'syntax');
        }

        return $name;
    }

    /** @param array<array-key, mixed> $methods
     *  @return list<string>
     */
    public static function routeMethods(array $methods): array
    {
        $count = count($methods);
        if (!array_is_list($methods)) {
            self::invalidMethods($count, 'not_list');
        }
        if (0 === $count) {
            self::invalidMethods($count, 'empty');
        }
        if ($count > 16) {
            self::invalidMethods($count, 'too_many');
        }

        $validated = [];
        foreach ($methods as $position => $method) {
            if (!is_string($method)) {
                self::invalidMethod($position, 0, 'not_string');
            }
            $length = strlen($method);
            if (0 === $length) {
                self::invalidMethod($position, $length, 'empty');
            }
            if ($length > 32) {
                self::invalidMethod($position, $length, 'too_long');
            }
            if (!self::isHttpToken($method)) {
                self::invalidMethod($position, $length, 'syntax');
            }
            if (self::containsLowercaseAscii($method)) {
                self::invalidMethod($position, $length, 'not_canonical');
            }
            if (in_array($method, $validated, true)) {
                self::invalidMethod($position, $length, 'duplicate');
            }
            $validated[] = sprintf('%s', $method);
        }

        usort($validated, 'strcmp');

        return $validated;
    }

    /** @return array{segments: list<array{kind: 'literal'|'parameter', value: string}>, parameter_names: list<string>, specificity: string, structural_pattern: string} */
    public static function routeTemplate(string $template): array
    {
        $length = strlen($template);
        if (0 === $length) {
            self::invalidTemplate($length, 'empty');
        }
        if ($length > 2048) {
            self::invalidTemplate($length, 'too_long');
        }
        if ('/' !== $template[0]) {
            self::invalidTemplate($length, 'missing_leading_slash');
        }
        if ('/' !== $template && str_ends_with($template, '/')) {
            self::invalidTemplate($length, 'trailing_slash');
        }
        if ('/' === $template) {
            return ['segments' => [], 'parameter_names' => [], 'specificity' => '', 'structural_pattern' => '/'];
        }

        $segments = explode('/', substr($template, 1));
        if (count($segments) > 32) {
            self::invalidTemplate($length, 'too_many_segments');
        }

        $parameterNames = [];
        $parsedSegments = [];
        $specificity = '';
        $structuralSegments = [];
        foreach ($segments as $position => $segment) {
            if ('' === $segment) {
                self::invalidTemplate($length, 'empty_segment');
            }
            if (strlen($segment) > 128) {
                self::invalidTemplate($length, 'segment_too_long');
            }
            if (str_contains($segment, '{') || str_contains($segment, '}')) {
                if (1 !== preg_match('/^\{([A-Za-z_][A-Za-z0-9_]{0,63})\}$/D', $segment, $matches)) {
                    self::invalidTemplate($length, 'invalid_parameter');
                }
                $parameterName = $matches[1];
                if (in_array($parameterName, $parameterNames, true)) {
                    throw new InvalidRouteDefinitionException(RoutingDiagnostic::DuplicateRouteParameter, [
                        'parameter_position' => $position,
                    ]);
                }
                $parameterNames[] = $parameterName;
                $parsedSegments[] = ['kind' => 'parameter', 'value' => $parameterName];
                $specificity .= '0';
                $structuralSegments[] = '{}';
                continue;
            }

            self::validateEncodedSegment($segment, true, $length);
            $parsedSegments[] = ['kind' => 'literal', 'value' => $segment];
            $specificity .= '1';
            $structuralSegments[] = $segment;
        }

        return [
            'segments' => $parsedSegments,
            'parameter_names' => $parameterNames,
            'specificity' => $specificity,
            'structural_pattern' => '/' . implode('/', $structuralSegments),
        ];
    }

    public static function requestMethod(string $method): string
    {
        $length = strlen($method);
        if (0 === $length) {
            self::invalidRequest('method', $length, 'empty');
        }
        if ($length > 32) {
            self::invalidRequest('method', $length, 'too_long');
        }
        if (!self::isHttpToken($method)) {
            self::invalidRequest('method', $length, 'syntax');
        }

        return $method;
    }

    /** @return list<string> */
    public static function requestPath(string $path): array
    {
        $length = strlen($path);
        if (0 === $length || '/' === $path) {
            return [];
        }
        if ($length > 8192) {
            self::invalidRequest('path', $length, 'too_long');
        }
        if ('/' !== $path[0]) {
            self::invalidRequest('path', $length, 'missing_leading_slash');
        }

        $segments = explode('/', substr($path, 1));
        if (count($segments) > 64) {
            self::invalidRequest('path', $length, 'too_many_segments');
        }
        foreach ($segments as $segment) {
            if (strlen($segment) > 1024) {
                self::invalidRequest('path', $length, 'segment_too_long');
            }
            self::validateEncodedSegment($segment, false, $length);
        }

        return $segments;
    }

    /** @param array<array-key, mixed> $parameters
     *  @return array<string, string>
     */
    public static function routeMatchParameters(string $template, array $parameters): array
    {
        $parameterCount = count($parameters);
        $parameterNames = self::routeTemplate($template)['parameter_names'];
        if ($parameterCount !== count($parameterNames)) {
            self::invalidMatch($parameterCount, 'key_mismatch');
        }
        foreach ($parameters as $key => $_value) {
            if (!is_string($key) || !in_array($key, $parameterNames, true)) {
                self::invalidMatch($parameterCount, 'key_mismatch');
            }
        }

        $validated = [];
        foreach ($parameterNames as $name) {
            if (!array_key_exists($name, $parameters) || !is_string($parameters[$name])) {
                self::invalidMatch($parameterCount, array_key_exists($name, $parameters) ? 'invalid_value' : 'key_mismatch');
            }
            $value = $parameters[$name];
            if ('' === $value || strlen($value) > 1024 || !self::encodedSegmentIsValid($value)) {
                self::invalidMatch($parameterCount, 'invalid_value');
            }
            $validated[$name] = sprintf('%s', $value);
        }

        return $validated;
    }

    private static function invalidName(int $length, string $violation): never
    {
        throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteName, [
            'name_length' => $length,
            'violation' => $violation,
        ]);
    }

    private static function invalidMethods(int $count, string $violation): never
    {
        throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteMethods, [
            'entry_count' => $count,
            'violation' => $violation,
        ]);
    }

    private static function invalidMethod(int $position, int $length, string $violation): never
    {
        throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteMethod, [
            'method_position' => $position,
            'method_length' => $length,
            'violation' => $violation,
        ]);
    }

    private static function invalidTemplate(int $length, string $violation): never
    {
        throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteTemplate, [
            'template_length' => $length,
            'violation' => $violation,
        ]);
    }

    private static function invalidMatch(int $count, string $violation): never
    {
        throw new InvalidRouteDefinitionException(RoutingDiagnostic::InvalidRouteMatch, [
            'parameter_count' => $count,
            'violation' => $violation,
        ]);
    }

    private static function invalidRequest(string $component, int $length, string $violation): never
    {
        throw new InvalidRouteRequestException(RoutingDiagnostic::InvalidRouteRequest, [
            'component' => $component,
            'actual_length' => $length,
            'violation' => $violation,
        ]);
    }

    private static function isHttpToken(string $value): bool
    {
        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            $byte = ord($value[$position]);
            if (($byte >= 48 && $byte <= 57) || ($byte >= 65 && $byte <= 90) || ($byte >= 97 && $byte <= 122)
                || str_contains("!#$%&'*+-.^_`|~", $value[$position])) {
                continue;
            }

            return false;
        }

        return true;
    }

    private static function containsLowercaseAscii(string $value): bool
    {
        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            $byte = ord($value[$position]);
            if ($byte >= 97 && $byte <= 122) {
                return true;
            }
        }

        return false;
    }

    private static function validateEncodedSegment(string $segment, bool $template, int $totalLength): void
    {
        for ($position = 0, $length = strlen($segment); $position < $length; ++$position) {
            $byte = $segment[$position];
            if ('%' === $byte) {
                if ($position + 2 >= $length || !self::isHex($segment[$position + 1]) || !self::isHex($segment[$position + 2])) {
                    if ($template) {
                        self::invalidTemplate($totalLength, 'invalid_percent_triplet');
                    }
                    self::invalidRequest('path', $totalLength, 'invalid_percent_triplet');
                }
                $position += 2;
                continue;
            }
            if (!self::isPchar($byte)) {
                if ($template) {
                    self::invalidTemplate($totalLength, 'invalid_literal');
                }
                self::invalidRequest('path', $totalLength, 'invalid_path_byte');
            }
        }
    }

    private static function encodedSegmentIsValid(string $segment): bool
    {
        for ($position = 0, $length = strlen($segment); $position < $length; ++$position) {
            if ('%' === $segment[$position]) {
                if ($position + 2 >= $length || !self::isHex($segment[$position + 1]) || !self::isHex($segment[$position + 2])) {
                    return false;
                }
                $position += 2;
                continue;
            }
            if (!self::isPchar($segment[$position])) {
                return false;
            }
        }

        return true;
    }

    private static function isPchar(string $byte): bool
    {
        $ordinal = ord($byte);

        return ($ordinal >= 48 && $ordinal <= 57)
            || ($ordinal >= 65 && $ordinal <= 90)
            || ($ordinal >= 97 && $ordinal <= 122)
            || str_contains("-._~!$&'()*+,;=:@", $byte);
    }

    private static function isHex(string $byte): bool
    {
        $ordinal = ord($byte);

        return ($ordinal >= 48 && $ordinal <= 57)
            || ($ordinal >= 65 && $ordinal <= 70)
            || ($ordinal >= 97 && $ordinal <= 102);
    }
}
