<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Uri;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
use Psr\Http\Message\UriInterface;
use Throwable;

/** Owns deterministic profile, snapshot, target, body-metadata, and exact-read validation. */
#[Api(ApiStatus::Internal)]
final class CgiRequestValidation
{
    private function __construct() {}

    /**
     * @param array<array-key, mixed> $server
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $files
     * @return array<string, string>
     */
    public static function snapshot(string $sapi, array $server, array $post, array $files): array
    {
        if ('cgi-fcgi' !== $sapi) {
            self::reject(PhpCgiDiagnostic::InvalidHostProfile, 'sapi_mismatch');
        }
        if (!array_key_exists('GATEWAY_INTERFACE', $server) || 'CGI/1.1' !== $server['GATEWAY_INTERFACE']) {
            self::reject(PhpCgiDiagnostic::InvalidHostProfile, 'gateway_interface_mismatch');
        }
        if (!array_key_exists('FCGI_ROLE', $server) || 'RESPONDER' !== $server['FCGI_ROLE']) {
            self::reject(PhpCgiDiagnostic::InvalidHostProfile, 'fastcgi_role_mismatch');
        }

        $keys = [];
        foreach ($server as $key => $_value) {
            if (!is_string($key)) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, 'server_key_not_string');
            }
            $keys[] = $key;
        }
        usort($keys, static fn(string $left, string $right): int => strcmp($left, $right));

        foreach ($keys as $key) {
            if (strlen($key) > 128) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, 'server_key_too_long');
            }
        }
        foreach ($keys as $key) {
            if (!in_array($key, self::nonHttpKeys(), true) && !str_starts_with($key, 'HTTP_')) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, 'server_key_not_allowed');
            }
        }
        foreach ($keys as $key) {
            if (str_starts_with($key, 'HTTP_') && 1 !== preg_match('/^[A-Z0-9_]+$/D', substr($key, 5))) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, 'http_key_suffix_invalid');
            }
        }
        foreach (self::requiredKeys() as $key) {
            if (!array_key_exists($key, $server)) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, 'required_server_key_missing');
            }
        }

        $orderedKeys = self::orderedKeys($keys);
        $snapshot = [];
        foreach ($orderedKeys as $key) {
            $value = $server[$key];
            if (!is_string($value)) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, 'server_value_not_string');
            }
            $snapshot[$key] = $value;
        }

        self::ip($snapshot['REMOTE_ADDR'], 'remote_address_invalid');
        self::port($snapshot['REMOTE_PORT'] ?? null, 'remote_port_invalid');
        if (array_key_exists('SERVER_ADDR', $snapshot)) {
            self::ip($snapshot['SERVER_ADDR'], 'server_address_invalid');
        }
        self::boundedScalar($snapshot['SERVER_NAME'] ?? null, 'server_name_invalid');
        self::port($snapshot['SERVER_PORT'] ?? null, 'server_port_invalid');
        self::boundedScalar($snapshot['SERVER_SOFTWARE'] ?? null, 'server_software_invalid');
        self::boundedScalar($snapshot['SCRIPT_NAME'] ?? null, 'script_name_invalid');

        if ([] !== $post) {
            self::reject(PhpCgiDiagnostic::InvalidRequestBody, 'post_data_present');
        }
        if ([] !== $files) {
            self::reject(PhpCgiDiagnostic::InvalidRequestBody, 'upload_data_present');
        }

        return $snapshot;
    }

    public static function method(string $method): string
    {
        if ('' === $method) {
            self::reject(PhpCgiDiagnostic::InvalidRequestMethod, 'method_empty');
        }
        if (strlen($method) > 32) {
            self::reject(PhpCgiDiagnostic::InvalidRequestMethod, 'method_too_long');
        }
        if (1 !== preg_match("/^[!#$%&'*+\\-.^_`|~0-9A-Z]+$/D", $method)) {
            self::reject(PhpCgiDiagnostic::InvalidRequestMethod, 'method_not_uppercase_token');
        }

        return $method;
    }

    public static function protocol(string $protocol): string
    {
        return match ($protocol) {
            'HTTP/1.0' => '1.0',
            'HTTP/1.1' => '1.1',
            'HTTP/2' => '2',
            'HTTP/2.0' => '2.0',
            'HTTP/3' => '3',
            'HTTP/3.0' => '3.0',
            default => self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'protocol_unsupported'),
        };
    }

    /** @return array{string, string, string} */
    public static function target(string $target, string $queryString): array
    {
        if ('' === $target) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'target_empty');
        }
        if (strlen($target) > 8192) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'target_too_long');
        }
        if (!str_starts_with($target, '/')) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'target_not_origin_form');
        }
        if (str_contains($target, '#')) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'target_fragment_forbidden');
        }
        if (self::containsTargetUnsafeByte($target)) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'target_unsafe_byte');
        }
        if (!self::validPercentTriplets($target)) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'target_percent_invalid');
        }
        if (strlen($queryString) > 8192) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'query_string_too_long');
        }

        $separator = strpos($target, '?');
        $path = false === $separator ? $target : substr($target, 0, $separator);
        $targetQuery = false === $separator ? '' : substr($target, $separator + 1);
        if ($targetQuery !== $queryString) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'query_string_mismatch');
        }

        return [$target, $path, $targetQuery];
    }

    public static function scheme(?string $https): string
    {
        if (null === $https || '' === $https || 'off' === $https) {
            return 'http';
        }
        if ('on' === $https) {
            return 'https';
        }

        self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'https_value_invalid');
    }

    public static function authority(string $authority): string
    {
        if ('' === $authority) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'authority_empty');
        }

        return $authority;
    }

    public static function contentLength(?string $contentLength): int
    {
        if (null === $contentLength || '' === $contentLength) {
            return 0;
        }
        if (1 !== preg_match('/^(?:0|[1-9][0-9]*)$/D', $contentLength)) {
            self::reject(PhpCgiDiagnostic::InvalidRequestBody, 'content_length_invalid');
        }
        if (strlen($contentLength) > 7 || (7 === strlen($contentLength) && strcmp($contentLength, '1048576') > 0)) {
            self::reject(PhpCgiDiagnostic::InvalidRequestBody, 'body_too_large');
        }

        return (int) $contentLength;
    }

    public static function rejectMultipart(?string $contentType): void
    {
        if (null === $contentType || '' === $contentType) {
            return;
        }
        $separator = strpos($contentType, ';');
        $mediaType = false === $separator ? $contentType : substr($contentType, 0, $separator);
        if ('multipart/form-data' === strtolower(trim($mediaType, " \t"))) {
            self::reject(PhpCgiDiagnostic::InvalidRequestBody, 'multipart_unsupported');
        }
    }

    public static function uri(string $scheme, string $authority, string $path, string $query): UriInterface
    {
        $uri = new Uri($scheme . '://' . $authority . '/');
        if (
            $scheme !== $uri->getScheme()
            || '' === $uri->getHost()
            || '' !== $uri->getUserInfo()
            || '/' !== $uri->getPath()
            || '' !== $uri->getQuery()
            || '' !== $uri->getFragment()
        ) {
            self::reject(PhpCgiDiagnostic::InvalidRequestTarget, 'authority_invalid');
        }

        return $uri->withPath($path)->withQuery($query);
    }

    /** @return resource */
    public static function bodyResource(mixed $input, int $length): mixed
    {
        if (!is_resource($input)) {
            $violation = 'resource (closed)' === get_debug_type($input) ? 'input_closed' : 'input_not_stream';
            throw new CgiRequestReadException($violation);
        }
        if ('stream' !== get_resource_type($input)) {
            throw new CgiRequestReadException('input_not_stream');
        }

        $metadata = self::metadata($input);
        if (!is_array($metadata) || !isset($metadata['mode']) || !is_string($metadata['mode'])) {
            throw new CgiRequestReadException('input_metadata_failed');
        }
        if (!str_starts_with($metadata['mode'], 'r') && !str_contains($metadata['mode'], '+')) {
            throw new CgiRequestReadException('input_unreadable');
        }

        $body = @fopen('php://temp', 'w+b');
        if (false === $body) {
            throw new CgiRequestReadException('body_stream_open_failed');
        }

        try {
            $remaining = $length;
            while ($remaining > 0) {
                $chunk = @fread($input, min(8192, $remaining));
                if (false === $chunk) {
                    throw new CgiRequestReadException('input_read_failed');
                }
                if ('' === $chunk) {
                    throw new CgiRequestReadException('input_early_eof');
                }

                $offset = 0;
                $chunkLength = strlen($chunk);
                while ($offset < $chunkLength) {
                    $written = @fwrite($body, substr($chunk, $offset));
                    if (false === $written || 0 === $written) {
                        throw new CgiRequestReadException('body_stream_write_failed');
                    }
                    $offset += $written;
                }
                $remaining -= $chunkLength;
            }
            if (!@rewind($body)) {
                throw new CgiRequestReadException('body_stream_rewind_failed');
            }

            return $body;
        } catch (Throwable $failure) {
            try {
                @fclose($body);
            } catch (Throwable) {
            }
            throw $failure;
        }
    }

    /** @return list<string> */
    private static function requiredKeys(): array
    {
        return [
            'GATEWAY_INTERFACE', 'FCGI_ROLE', 'REQUEST_METHOD', 'SERVER_PROTOCOL',
            'REQUEST_URI', 'QUERY_STRING', 'HTTP_HOST', 'REMOTE_ADDR',
        ];
    }

    /** @return list<string> */
    private static function optionalKeys(): array
    {
        return [
            'CONTENT_LENGTH', 'CONTENT_TYPE', 'HTTPS', 'REMOTE_PORT', 'SERVER_ADDR',
            'SERVER_NAME', 'SERVER_PORT', 'SERVER_SOFTWARE', 'SCRIPT_NAME',
        ];
    }

    /** @return list<string> */
    private static function nonHttpKeys(): array
    {
        return [...self::requiredKeys(), ...self::optionalKeys()];
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    private static function orderedKeys(array $keys): array
    {
        $ordered = [];
        foreach ([...self::requiredKeys(), ...self::optionalKeys()] as $key) {
            if (in_array($key, $keys, true)) {
                $ordered[] = $key;
            }
        }

        $httpKeys = array_values(array_filter(
            $keys,
            static fn(string $key): bool => str_starts_with($key, 'HTTP_') && 'HTTP_HOST' !== $key,
        ));
        usort($httpKeys, static fn(string $left, string $right): int => strcmp($left, $right));

        return [...$ordered, ...$httpKeys];
    }

    private static function ip(string $value, string $violation): void
    {
        if (strlen($value) < 1 || strlen($value) > 45 || false === filter_var($value, FILTER_VALIDATE_IP)) {
            self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, $violation);
        }
    }

    private static function port(?string $value, string $violation): void
    {
        if (null === $value || '' === $value) {
            return;
        }
        if (1 !== preg_match('/^(?:[1-9]|[1-9][0-9]{1,3}|[1-5][0-9]{4}|6[0-4][0-9]{3}|65[0-4][0-9]{2}|655[0-2][0-9]|6553[0-5])$/D', $value)) {
            self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, $violation);
        }
    }

    private static function boundedScalar(?string $value, string $violation): void
    {
        if (null === $value) {
            return;
        }
        if (strlen($value) > 8192) {
            self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, $violation);
        }
        for ($position = 0, $length = strlen($value); $position < $length; ++$position) {
            $byte = ord($value[$position]);
            if ($byte < 32 || 127 === $byte) {
                self::reject(PhpCgiDiagnostic::InvalidServerSnapshot, $violation);
            }
        }
    }

    private static function containsTargetUnsafeByte(string $target): bool
    {
        for ($position = 0, $length = strlen($target); $position < $length; ++$position) {
            $byte = ord($target[$position]);
            if ($byte <= 32 || 127 === $byte) {
                return true;
            }
        }

        return false;
    }

    private static function validPercentTriplets(string $target): bool
    {
        for ($position = 0, $length = strlen($target); $position < $length; ++$position) {
            if ('%' === $target[$position]) {
                if ($position + 2 >= $length || !ctype_xdigit($target[$position + 1] . $target[$position + 2])) {
                    return false;
                }
                $position += 2;
            }
        }

        return true;
    }

    /** @param resource $resource */
    private static function metadata(mixed $resource): mixed
    {
        return @stream_get_meta_data($resource);
    }

    private static function reject(PhpCgiDiagnostic $diagnostic, string $violation): never
    {
        throw new InvalidCgiRequestException($diagnostic, $violation);
    }
}
