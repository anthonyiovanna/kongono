<?php

declare(strict_types=1);

namespace Kongono\Tests\External\PhpCgi;

use RuntimeException;
use Throwable;

/** Sequential bounded FastCGI client restricted to literal IPv4 loopback. */
final class FastCgiClient
{
    private const int MAX_RECORDS = 4096;
    private const int MAX_STDOUT = 1_200_000;
    private const int MAX_STDERR = 65_536;

    /** @var null|resource */
    private mixed $socket = null;
    private int $nextRequestId = 1;

    public function __construct(private readonly int $port)
    {
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('fastcgi_port_invalid');
        }
    }

    /** @param array<string, string> $parameters */
    public function request(array $parameters, string $body = '', bool $keepConnection = false): FastCgiResponse
    {
        $decoded = $this->exchange($parameters, $body, $keepConnection);

        return FastCgiResponse::fromDecoded(
            $decoded['stdout'],
            $decoded['stderr'],
            $decoded['application_status'],
            $decoded['protocol_status'],
        );
    }

    /** @param array<string, string> $parameters */
    public function requestExpectingHostFailureEnvelope(array $parameters, string $body = ''): void
    {
        $decoded = $this->exchange($parameters, $body, false);
        if (
            "Status: 500 Internal Server Error\r\n\r\n" !== $decoded['stdout']
            || '' !== $decoded['stderr']
            || 0 !== $decoded['application_status']
            || 0 !== $decoded['protocol_status']
        ) {
            throw new RuntimeException('fastcgi_host_failure_envelope_invalid');
        }
    }

    /** @param array<string, string> $parameters */
    public function submitAndDisconnect(array $parameters, string $body): void
    {
        $requestId = $this->allocateRequestId();
        try {
            $socket = $this->connection();
            $this->writeAll($socket, FastCgiRecordCodec::encodeRequest($requestId, $parameters, $body, true));
        } finally {
            $this->close();
        }
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param array<string, string> $parameters
     * @return array{stdout: string, stderr: string, application_status: int, protocol_status: int}
     */
    private function exchange(array $parameters, string $body, bool $keepConnection): array
    {
        $requestId = $this->allocateRequestId();
        try {
            $socket = $this->connection();
            $deadline = microtime(true) + 15.0;
            $this->writeAll($socket, FastCgiRecordCodec::encodeRequest($requestId, $parameters, $body, $keepConnection));
            $stdout = '';
            $stderr = '';
            $end = null;
            for ($records = 0; $records < self::MAX_RECORDS; ++$records) {
                $header = FastCgiRecordCodec::decodeHeader($this->readExact($socket, 8, $deadline));
                if ($requestId !== $header['request_id']) {
                    throw new RuntimeException('fastcgi_request_id_mismatch');
                }
                $content = $this->readExact($socket, $header['content_length'], $deadline);
                $padding = $this->readExact($socket, $header['padding_length'], $deadline);
                FastCgiRecordCodec::assertPadding($padding);
                if (FastCgiRecordCodec::STDOUT === $header['type']) {
                    $stdout .= $content;
                    if (strlen($stdout) > self::MAX_STDOUT) {
                        throw new RuntimeException('fastcgi_stdout_exceeded');
                    }
                    continue;
                }
                if (FastCgiRecordCodec::STDERR === $header['type']) {
                    $stderr .= $content;
                    if (strlen($stderr) > self::MAX_STDERR) {
                        throw new RuntimeException('fastcgi_stderr_exceeded');
                    }
                    continue;
                }
                if (FastCgiRecordCodec::END_REQUEST === $header['type']) {
                    $end = FastCgiRecordCodec::decodeEndRequest($content);
                    break;
                }
                throw new RuntimeException('fastcgi_response_type_unsupported');
            }
            if (null === $end) {
                throw new RuntimeException('fastcgi_end_request_missing');
            }
            if (!$keepConnection) {
                $this->close();
            }

            return [
                'stdout' => $stdout,
                'stderr' => $stderr,
                'application_status' => $end['application_status'],
                'protocol_status' => $end['protocol_status'],
            ];
        } catch (Throwable $failure) {
            $this->close();
            throw $failure;
        }
    }

    /** @return resource */
    private function connection(): mixed
    {
        if (is_resource($this->socket)) {
            return $this->socket;
        }
        $errno = 0;
        $error = '';
        $socket = @stream_socket_client(
            'tcp://127.0.0.1:' . $this->port,
            $errno,
            $error,
            2.0,
            STREAM_CLIENT_CONNECT,
        );
        if (false === $socket) {
            throw new RuntimeException('fastcgi_connect_failed');
        }
        if (!stream_set_timeout($socket, 5)) {
            fclose($socket);
            throw new RuntimeException('fastcgi_timeout_configuration_failed');
        }
        $this->socket = $socket;

        return $socket;
    }

    /** @param resource $socket */
    private function writeAll(mixed $socket, string $bytes): void
    {
        $deadline = microtime(true) + 5.0;
        for ($offset = 0, $length = strlen($bytes); $offset < $length;) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('fastcgi_write_timeout');
            }
            $written = @fwrite($socket, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('fastcgi_write_failed');
            }
            $offset += $written;
        }
    }

    /** @param resource $socket */
    private function readExact(mixed $socket, int $length, float $deadline): string
    {
        $bytes = '';
        while (strlen($bytes) < $length) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('fastcgi_read_timeout');
            }
            $remaining = $length - strlen($bytes);
            if ($remaining < 1) {
                throw new RuntimeException('fastcgi_read_length_invalid');
            }
            $chunk = @fread($socket, $remaining);
            if (false === $chunk || '' === $chunk) {
                $metadata = stream_get_meta_data($socket);
                if ($metadata['timed_out']) {
                    throw new RuntimeException('fastcgi_read_timeout');
                }
                throw new RuntimeException('fastcgi_response_truncated');
            }
            $bytes .= $chunk;
        }

        return $bytes;
    }

    private function allocateRequestId(): int
    {
        if ($this->nextRequestId > 65535) {
            throw new RuntimeException('fastcgi_request_id_exhausted');
        }

        return $this->nextRequestId++;
    }
}
