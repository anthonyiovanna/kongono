<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Stream;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Exception\CgiResponseEmissionException;
use Kongono\PhpCgi\Exception\InvalidCgiResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/** Owns bounded response preflight, staging, commitment, and exact emission order. */
#[Api(ApiStatus::Internal)]
final class CgiResponseEmission
{
    private const BODY_LIMIT = 1048576;
    private const CHUNK_BYTES = 8192;
    private const HEADER_LIMIT = 128;
    private const HEADER_VALUE_LIMIT = 32;
    private const HEADER_VALUE_BYTES = 8192;
    private const HEADER_BYTES = 65536;

    private function __construct() {}

    public static function emit(
        ResponseInterface $response,
        string $requestMethod,
        CgiEmissionTarget $target,
    ): void {
        self::assertHeadersUnsent($target);
        $method = CgiRequestValidation::method($requestMethod);
        $staged = self::stage($response, $target);

        try {
            self::assertHeadersUnsent($target);
            self::emitStaged($staged, $method, $target);
        } finally {
            $staged->body->close();
        }
    }

    private static function assertHeadersUnsent(CgiEmissionTarget $target): void
    {
        try {
            $headersSent = $target->headersSent();
        } catch (Throwable $failure) {
            throw self::preflightFailure('headers_already_sent', $failure);
        }

        if ($headersSent) {
            throw self::preflightFailure('headers_already_sent');
        }
    }

    private static function stage(
        ResponseInterface $response,
        CgiEmissionTarget $target,
    ): CgiStagedResponse {
        $status = $response->getStatusCode();
        self::assertHeadersUnsent($target);
        if ($status < 200 || $status > 599) {
            throw new InvalidCgiResponseException(
                PhpCgiDiagnostic::InvalidResponseStatus,
                'status_out_of_range',
            );
        }

        $protocol = $response->getProtocolVersion();
        self::assertHeadersUnsent($target);
        if (!in_array($protocol, ['1.0', '1.1', '2', '2.0', '3', '3.0'], true)) {
            throw new InvalidCgiResponseException(
                PhpCgiDiagnostic::InvalidResponseProtocol,
                'protocol_unsupported',
            );
        }

        $rawHeaders = $response->getHeaders();
        self::assertHeadersUnsent($target);
        $headers = self::headers($rawHeaders);

        $body = $response->getBody();
        self::assertHeadersUnsent($target);
        $readable = $body->isReadable();
        self::assertHeadersUnsent($target);
        if (!$readable) {
            throw new InvalidCgiResponseException(
                PhpCgiDiagnostic::InvalidResponseBody,
                'body_unreadable',
            );
        }

        return self::stageBody($status, $protocol, $headers, $body, $target);
    }

    /**
     * @param array<array-key, mixed> $rawHeaders
     * @return list<array{name: string, values: list<string>}>
     */
    private static function headers(array $rawHeaders): array
    {
        if (count($rawHeaders) > self::HEADER_LIMIT) {
            throw self::invalidHeaders('header_count_exceeded');
        }

        $headers = [];
        $normalizedNames = [];
        $totalBytes = 0;

        foreach ($rawHeaders as $name => $values) {
            if (!is_string($name)) {
                throw self::invalidHeaders('header_name_not_string');
            }
            if (!is_array($values) || !array_is_list($values)) {
                throw self::invalidHeaders('header_values_not_list');
            }
            if ([] === $values || count($values) > self::HEADER_VALUE_LIMIT) {
                throw self::invalidHeaders('header_value_count_invalid');
            }
            if (1 !== preg_match("/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/D", $name)) {
                throw self::invalidHeaders('header_name_invalid');
            }

            $normalized = strtolower($name);
            if (isset($normalizedNames[$normalized])) {
                throw self::invalidHeaders('header_name_duplicate');
            }
            $normalizedNames[$normalized] = true;

            if (in_array($normalized, [
                'status', 'content-length', 'transfer-encoding', 'connection', 'keep-alive',
                'proxy-authenticate', 'proxy-authorization', 'te', 'trailer', 'upgrade',
            ], true)) {
                throw self::invalidHeaders('header_forbidden');
            }

            $detachedValues = [];
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw self::invalidHeaders('header_value_not_string');
                }
                if (strlen($value) > self::HEADER_VALUE_BYTES) {
                    throw self::invalidHeaders('header_value_too_long');
                }
                if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $value)) {
                    throw self::invalidHeaders('header_value_invalid');
                }

                $totalBytes += strlen($name) + strlen($value);
                if ($totalBytes > self::HEADER_BYTES) {
                    throw self::invalidHeaders('header_bytes_exceeded');
                }
                $detachedValues[] = $value;
            }

            $headers[] = ['name' => $name, 'values' => $detachedValues];
        }

        return $headers;
    }

    private static function invalidHeaders(string $violation): InvalidCgiResponseException
    {
        return new InvalidCgiResponseException(
            PhpCgiDiagnostic::InvalidResponseHeaders,
            $violation,
        );
    }

    /**
     * @param list<array{name: string, values: list<string>}> $headers
     */
    private static function stageBody(
        int $status,
        string $protocol,
        array $headers,
        StreamInterface $body,
        CgiEmissionTarget $target,
    ): CgiStagedResponse {
        try {
            $resource = @fopen('php://temp', 'w+b');
        } catch (Throwable $failure) {
            throw self::preflightFailure('staging_open_failed', $failure);
        }
        if (false === $resource) {
            throw self::preflightFailure('staging_open_failed');
        }

        try {
            try {
                $staging = new Stream($resource);
            } catch (Throwable $failure) {
                throw self::preflightFailure('staging_open_failed', $failure);
            }

            $length = 0;
            while (true) {
                $eof = $body->eof();
                self::assertHeadersUnsent($target);
                if ($eof) {
                    break;
                }

                $chunk = $body->read(self::CHUNK_BYTES);
                self::assertHeadersUnsent($target);
                if (strlen($chunk) > self::CHUNK_BYTES) {
                    throw new InvalidCgiResponseException(
                        PhpCgiDiagnostic::InvalidResponseBody,
                        'body_read_exceeded_request',
                    );
                }
                if ('' === $chunk) {
                    $eof = $body->eof();
                    self::assertHeadersUnsent($target);
                    if (!$eof) {
                        throw new InvalidCgiResponseException(
                            PhpCgiDiagnostic::InvalidResponseBody,
                            'body_stalled',
                        );
                    }
                    break;
                }

                $length += strlen($chunk);
                if ($length > self::BODY_LIMIT) {
                    throw new InvalidCgiResponseException(
                        PhpCgiDiagnostic::InvalidResponseBody,
                        'body_too_large',
                    );
                }

                try {
                    $written = $staging->write($chunk);
                } catch (Throwable $failure) {
                    throw self::preflightFailure('staging_write_failed', $failure);
                }
                if (strlen($chunk) !== $written) {
                    throw self::preflightFailure('staging_write_failed');
                }
            }

            try {
                $staging->rewind();
            } catch (Throwable $failure) {
                throw self::preflightFailure('staging_rewind_failed', $failure);
            }

            return new CgiStagedResponse($status, $protocol, $headers, $staging, $length);
        } catch (Throwable $failure) {
            if (isset($staging)) {
                $staging->close();
            } else {
                @fclose($resource);
            }
            throw $failure;
        }
    }

    private static function emitStaged(
        CgiStagedResponse $staged,
        string $requestMethod,
        CgiEmissionTarget $target,
    ): void {
        $commitAttempted = true;

        try {
            $target->setStatus($staged->status);
        } catch (Throwable $failure) {
            throw self::emissionFailure('status_emit_failed', $failure, $commitAttempted);
        }

        foreach ($staged->headers as $header) {
            foreach ($header['values'] as $position => $value) {
                try {
                    $target->emitHeader($header['name'], $value, 0 === $position);
                } catch (Throwable $failure) {
                    throw self::emissionFailure('header_emit_failed', $failure, $commitAttempted);
                }
            }
        }

        try {
            $target->emitHeader('Content-Length', (string) $staged->length, true);
        } catch (Throwable $failure) {
            throw self::emissionFailure('content_length_emit_failed', $failure, $commitAttempted);
        }

        if ('HEAD' !== $requestMethod) {
            $remaining = $staged->length;
            while ($remaining > 0) {
                try {
                    $chunk = $staged->body->read(min(self::CHUNK_BYTES, $remaining));
                    if ('' === $chunk) {
                        throw self::emissionFailure('staged_body_read_failed', commitAttempted: $commitAttempted);
                    }
                } catch (CgiResponseEmissionException $failure) {
                    throw $failure;
                } catch (Throwable $failure) {
                    throw self::emissionFailure('staged_body_read_failed', $failure, $commitAttempted);
                }

                try {
                    $written = $target->writeBody($chunk);
                } catch (Throwable $failure) {
                    throw self::emissionFailure('output_write_failed', $failure, $commitAttempted);
                }
                if (strlen($chunk) !== $written) {
                    throw self::emissionFailure('output_write_failed', commitAttempted: $commitAttempted);
                }
                $remaining -= strlen($chunk);
            }
        }

        $completed = true;
    }

    private static function preflightFailure(
        string $violation,
        ?Throwable $previous = null,
    ): CgiResponseEmissionException {
        return new CgiResponseEmissionException(
            PhpCgiDiagnostic::ResponsePreflightFailed,
            $violation,
            false,
            $previous,
        );
    }

    private static function emissionFailure(
        string $violation,
        ?Throwable $previous = null,
        bool $commitAttempted = true,
    ): CgiResponseEmissionException {
        return new CgiResponseEmissionException(
            PhpCgiDiagnostic::ResponseEmissionFailed,
            $violation,
            $commitAttempted,
            $previous,
        );
    }
}
