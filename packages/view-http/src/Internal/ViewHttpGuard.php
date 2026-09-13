<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Lifecycle\RenderOperationState;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewHttp\Exception\InvalidViewHttpPolicyException;
use Kongono\ViewHttp\Policy\ViewResponseHeader;

/** Validates all bounded inputs before PSR factories are invoked. */
#[Api(ApiStatus::Internal)]
final class ViewHttpGuard
{
    private const int MAX_HEADERS = 32;
    private const int MAX_HEADER_NAME_BYTES = 64;
    private const int MAX_VALUES = 8;
    private const int MAX_VALUE_BYTES = 1024;
    private const int MAX_HEADER_BYTES = 16384;
    private const array RESERVED = ['connection', 'content-length', 'content-type', 'host', 'keep-alive', 'proxy-connection', 'te', 'trailer', 'transfer-encoding', 'upgrade'];

    /** @param array<array-key, mixed> $values
     *  @return list<string>
     */
    public static function header(string $name, array $values): array
    {
        if (strlen($name) < 1 || strlen($name) > self::MAX_HEADER_NAME_BYTES) {
            throw InvalidViewHttpPolicyException::policy('header_name_length', self::MAX_HEADER_NAME_BYTES, strlen($name));
        }
        if (1 !== preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $name)) {
            throw InvalidViewHttpPolicyException::policy('header_name_grammar');
        }
        if (in_array(strtolower($name), self::RESERVED, true)) {
            throw InvalidViewHttpPolicyException::policy('header_name_reserved');
        }
        if (!array_is_list($values)) {
            throw InvalidViewHttpPolicyException::policy('header_values_not_list');
        }
        if (count($values) < 1 || count($values) > self::MAX_VALUES) {
            throw InvalidViewHttpPolicyException::policy('header_value_count', self::MAX_VALUES, count($values));
        }
        $validated = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw InvalidViewHttpPolicyException::policy('header_value_type');
            }
            if (strlen($value) > self::MAX_VALUE_BYTES) {
                throw InvalidViewHttpPolicyException::policy('header_value_length', self::MAX_VALUE_BYTES, strlen($value));
            }
            if (1 !== preg_match('/^(?:\x09|[\x20-\x7E])*$/D', $value)) {
                throw InvalidViewHttpPolicyException::policy('header_value_bytes');
            }
            $validated[] = $value;
        }

        return $validated;
    }

    /** @param array<array-key, mixed> $headers
     *  @return list<ViewResponseHeader>
     */
    public static function policy(int $statusCode, string $reasonPhrase, array $headers): array
    {
        if ($statusCode < 200 || $statusCode > 599) {
            throw InvalidViewHttpPolicyException::policy('status_code_range', statusCode: $statusCode);
        }
        if (strlen($reasonPhrase) > 128) {
            throw InvalidViewHttpPolicyException::policy('reason_phrase_length', 128, strlen($reasonPhrase), $statusCode);
        }
        if ('' !== $reasonPhrase && 1 !== preg_match('/^[\x20-\x7E]+$/D', $reasonPhrase)) {
            throw InvalidViewHttpPolicyException::policy('reason_phrase_bytes', statusCode: $statusCode);
        }
        if (!array_is_list($headers)) {
            throw InvalidViewHttpPolicyException::policy('headers_not_list', statusCode: $statusCode);
        }
        if (count($headers) > self::MAX_HEADERS) {
            throw InvalidViewHttpPolicyException::policy('header_count', self::MAX_HEADERS, count($headers), $statusCode, count($headers));
        }
        $validated = [];
        $names = [];
        $bytes = 0;
        foreach ($headers as $header) {
            if (!$header instanceof ViewResponseHeader) {
                throw InvalidViewHttpPolicyException::policy('header_entry_type');
            }
            $lower = strtolower($header->name());
            if (isset($names[$lower])) {
                throw InvalidViewHttpPolicyException::policy('header_name_duplicate');
            }
            $names[$lower] = true;
            $bytes += strlen($header->name());
            foreach ($header->values() as $value) {
                $bytes += strlen($value);
            }
            if ($bytes > self::MAX_HEADER_BYTES) {
                throw InvalidViewHttpPolicyException::policy('header_bytes', self::MAX_HEADER_BYTES, $bytes);
            }
            $validated[] = $header;
        }

        return $validated;
    }

    public static function completedResult(RenderResult $result): void
    {
        if (RenderOperationState::Completed !== $result->state()) {
            throw InvalidViewHttpPolicyException::renderResult('operation_state');
        }
        $bytes = $result->output()->bytes();
        if (strlen($bytes) !== $result->metadata()->outputByteCount()) {
            throw InvalidViewHttpPolicyException::renderResult('output_byte_count', strlen($bytes));
        }
        if (!hash_equals(hash('sha256', $bytes), $result->metadata()->outputSha256())) {
            throw InvalidViewHttpPolicyException::renderResult('output_sha256', strlen($bytes));
        }
    }
}
