<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi;

use RuntimeException;

/** Owns the bounded FastCGI v1 record grammar used only by Phase 3 acceptance. */
final class FastCgiRecordCodec
{
    public const int BEGIN_REQUEST = 1;
    public const int ABORT_REQUEST = 2;
    public const int END_REQUEST = 3;
    public const int PARAMS = 4;
    public const int STDIN = 5;
    public const int STDOUT = 6;
    public const int STDERR = 7;

    private const int VERSION = 1;
    private const int RESPONDER = 1;
    private const int KEEP_CONN = 1;
    private const int MAX_CONTENT_BYTES = 65535;

    private function __construct() {}

    /** @param array<string, string> $parameters */
    public static function encodeRequest(
        int $requestId,
        array $parameters,
        string $body,
        bool $keepConnection,
    ): string {
        self::assertRequestId($requestId);
        $begin = pack('nCxxxxx', self::RESPONDER, $keepConnection ? self::KEEP_CONN : 0);
        $parameterRecords = '';
        $parameterContent = '';
        $parameterBytes = 0;
        foreach ($parameters as $name => $value) {
            if ('' === $name || strlen($name) > 1_200_000 || strlen($value) > 1_200_000) {
                throw new RuntimeException('fastcgi_parameter_invalid');
            }
            $pair = self::encodeLength(strlen($name)) . self::encodeLength(strlen($value)) . $name . $value;
            if (strlen($pair) > self::MAX_CONTENT_BYTES) {
                throw new RuntimeException('fastcgi_parameter_pair_exceeded');
            }
            if ('' !== $parameterContent && strlen($parameterContent) + strlen($pair) > self::MAX_CONTENT_BYTES) {
                $parameterRecords .= self::record(self::PARAMS, $requestId, $parameterContent);
                $parameterContent = '';
            }
            $parameterContent .= $pair;
            $parameterBytes += strlen($pair);
            if ($parameterBytes > 2_400_000) {
                throw new RuntimeException('fastcgi_parameters_exceeded');
            }
        }
        if ('' !== $parameterContent) {
            $parameterRecords .= self::record(self::PARAMS, $requestId, $parameterContent);
        }

        return self::record(self::BEGIN_REQUEST, $requestId, $begin)
            . $parameterRecords
            . self::record(self::PARAMS, $requestId, '')
            . self::records(self::STDIN, $requestId, $body)
            . self::record(self::STDIN, $requestId, '');
    }

    public static function encodeAbort(int $requestId): string
    {
        self::assertRequestId($requestId);

        return self::record(self::ABORT_REQUEST, $requestId, '');
    }

    /** @return array{type: int, request_id: int, content_length: int, padding_length: int} */
    public static function decodeHeader(string $header): array
    {
        if (8 !== strlen($header)) {
            throw new RuntimeException('fastcgi_header_truncated');
        }
        /** @var array{version: int, type: int, request_id: int, content_length: int, padding_length: int, reserved: int}|false $decoded */
        $decoded = unpack('Cversion/Ctype/nrequest_id/ncontent_length/Cpadding_length/Creserved', $header);
        if (false === $decoded) {
            throw new RuntimeException('fastcgi_header_malformed');
        }
        if (self::VERSION !== $decoded['version']) {
            throw new RuntimeException('fastcgi_version_invalid');
        }
        if (0 !== $decoded['reserved']) {
            throw new RuntimeException('fastcgi_reserved_invalid');
        }
        if ($decoded['padding_length'] > 7) {
            throw new RuntimeException('fastcgi_padding_invalid');
        }

        return [
            'type' => $decoded['type'],
            'request_id' => $decoded['request_id'],
            'content_length' => $decoded['content_length'],
            'padding_length' => $decoded['padding_length'],
        ];
    }

    /** @return array{application_status: int, protocol_status: int} */
    public static function decodeEndRequest(string $content): array
    {
        if (8 !== strlen($content)) {
            throw new RuntimeException('fastcgi_end_request_malformed');
        }
        /** @var array{application_status: int, protocol_status: int, reserved_1: int, reserved_2: int, reserved_3: int}|false $decoded */
        $decoded = unpack('Napplication_status/Cprotocol_status/Creserved_1/Creserved_2/Creserved_3', $content);
        if (false === $decoded) {
            throw new RuntimeException('fastcgi_end_request_malformed');
        }
        return [
            'application_status' => $decoded['application_status'],
            'protocol_status' => $decoded['protocol_status'],
        ];
    }

    public static function assertPadding(string $padding): void
    {
        if ('' !== trim($padding, "\0")) {
            throw new RuntimeException('fastcgi_padding_nonzero');
        }
    }

    private static function assertRequestId(int $requestId): void
    {
        if ($requestId < 1 || $requestId > 65535) {
            throw new RuntimeException('fastcgi_request_id_invalid');
        }
    }

    private static function encodeLength(int $length): string
    {
        if ($length < 0 || $length > 0x7fffffff) {
            throw new RuntimeException('fastcgi_name_value_length_invalid');
        }
        if ($length < 128) {
            return chr($length);
        }

        return pack('N', $length | 0x80000000);
    }

    private static function records(int $type, int $requestId, string $content): string
    {
        $records = '';
        for ($offset = 0, $length = strlen($content); $offset < $length; $offset += self::MAX_CONTENT_BYTES) {
            $records .= self::record($type, $requestId, substr($content, $offset, self::MAX_CONTENT_BYTES));
        }

        return $records;
    }

    private static function record(int $type, int $requestId, string $content): string
    {
        $length = strlen($content);
        if ($length > self::MAX_CONTENT_BYTES) {
            throw new RuntimeException('fastcgi_record_content_exceeded');
        }
        $paddingLength = (8 - ($length % 8)) % 8;

        return pack('CCnnCC', self::VERSION, $type, $requestId, $length, $paddingLength, 0)
            . $content
            . str_repeat("\0", $paddingLength);
    }
}
