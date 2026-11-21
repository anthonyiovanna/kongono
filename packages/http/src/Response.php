<?php

declare(strict_types=1);

namespace Kongono\Http;

use Closure;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/** Immutable PSR-7 Response with concise builders (json, html, text, redirect, stream). */
final class Response implements ResponseInterface
{
    private const array REASON_PHRASES = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Content',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /** @var array<string, list<string>> */
    private array $headers = [];

    /** @var array<string, string> */
    private array $headerNames = [];

    private StreamInterface $body;
    private string $reasonPhrase;

    /** @var (Closure(): iterable<string>)|null */
    private ?Closure $streamProducer = null;

    /** @param array<string, string|list<string>> $headers */
    public function __construct(
        private int $statusCode = 200,
        array $headers = [],
        ?StreamInterface $body = null,
        private string $protocolVersion = '1.1',
        string $reasonPhrase = '',
    ) {
        $this->body = $body ?? Stream::fromString('');
        $this->reasonPhrase = '' !== $reasonPhrase ? $reasonPhrase : (self::REASON_PHRASES[$statusCode] ?? '');

        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }
    }

    /** @param array<string, string|list<string>> $headers */
    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] ??= 'text/html; charset=utf-8';

        return new self($status, $headers, Stream::fromString($html));
    }

    /** @param array<string, string|list<string>> $headers */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] ??= 'application/json; charset=utf-8';
        $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new self($status, $headers, Stream::fromString($encoded));
    }

    /** @param array<string, string|list<string>> $headers */
    public static function text(string $text, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] ??= 'text/plain; charset=utf-8';

        return new self($status, $headers, Stream::fromString($text));
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, ['Location' => $location]);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    /**
     * Create a true streaming response (e.g. SSE or chunked downloads).
     *
     * @param Closure(): iterable<string>        $producer
     * @param array<string, string|list<string>> $headers
     */
    public static function stream(Closure $producer, int $status = 200, array $headers = []): self
    {
        $response = new self($status, $headers);
        $response->streamProducer = $producer;

        return $response;
    }

    /**
     * Create a first-class Server-Sent Events (SSE) response streaming SseEvent frames.
     *
     * @param Closure(): iterable<SseEvent>      $producer
     * @param array<string, string|list<string>> $headers
     */
    public static function sse(Closure $producer, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/event-stream; charset=utf-8';
        $headers['Cache-Control'] ??= 'no-cache, no-transform';
        $headers['X-Accel-Buffering'] ??= 'no';

        return self::stream(static function () use ($producer): iterable {
            foreach ($producer() as $event) {
                yield $event->format();
            }
        }, 200, $headers);
    }

    /** @return (Closure(): iterable<string>)|null */
    public function streamProducer(): ?Closure
    {
        return $this->streamProducer;
    }

    public function isStreaming(): bool
    {
        return null !== $this->streamProducer;
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function withCookie(Cookie $cookie): self
    {
        return $this->withAddedHeader('Set-Cookie', $cookie->toHeaderValue());
    }

    public function bodyString(): string
    {
        if (null !== $this->streamProducer) {
            $chunks = '';
            foreach (($this->streamProducer)() as $chunk) {
                $chunks .= $chunk;
            }

            return $chunks;
        }

        return (string) $this->body;
    }

    // --- PSR-7 ResponseInterface ---

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);
        if (!isset($this->headerNames[$normalized])) {
            return [];
        }

        return $this->headers[$this->headerNames[$normalized]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
        }
        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = is_array($value) ? array_values($value) : [$value];

        return $clone;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        $values = is_array($value) ? array_values($value) : [$value];
        if (isset($clone->headerNames[$normalized])) {
            $existing = $clone->headerNames[$normalized];
            $clone->headers[$existing] = [...$clone->headers[$existing], ...$values];
        } else {
            $clone->headerNames[$normalized] = $name;
            $clone->headers[$name] = $values;
        }

        return $clone;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]], $clone->headerNames[$normalized]);
        }

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        $clone->streamProducer = null;

        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = '' !== $reasonPhrase ? $reasonPhrase : (self::REASON_PHRASES[$code] ?? '');

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
