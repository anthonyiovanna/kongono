<?php

declare(strict_types=1);

namespace Kongono\Http;

use Closure;
use InvalidArgumentException;
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
        self::assertValidStatus($statusCode, $reasonPhrase);
        self::assertValidProtocolVersion($protocolVersion);

        $this->body = $body ?? Stream::fromString('');
        $this->reasonPhrase = '' !== $reasonPhrase ? $reasonPhrase : (self::REASON_PHRASES[$statusCode] ?? '');

        foreach ($headers as $name => $value) {
            $values = is_array($value) ? $value : [$value];
            self::assertValidHeader($name, $values);
            $normalized = strtolower($name);
            if (isset($this->headerNames[$normalized])) {
                unset($this->headers[$this->headerNames[$normalized]]);
            }
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = $values;
        }
    }

    /** @param array<string, string|list<string>> $headers */
    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $response = new self($status, $headers, Stream::fromString($html));

        return $response->hasHeader('Content-Type')
            ? $response
            : $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Create a Kongono\Live HTML fragment response for partial DOM swaps.
     *
     * @param array<string, string|list<string>> $headers
     */
    public static function fragment(
        string $html,
        int $status = 200,
        array $headers = [],
        ?string $pushUrl = null,
        ?string $redirect = null,
    ): self {
        $response = new self($status, $headers, Stream::fromString($html));
        if (!$response->hasHeader('Content-Type')) {
            $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        }
        if (!$response->hasHeader('Vary')) {
            $response = $response->withHeader('Vary', 'X-Kongono-Live');
        }
        $response = $response->withHeader('X-Kongono-Live', 'fragment');
        if (null !== $pushUrl && '' !== $pushUrl) {
            self::assertSafeNavigationUrl($pushUrl, 'pushUrl');
            $response = $response->withHeader('X-Kongono-Push-Url', $pushUrl);
        }
        if (null !== $redirect && '' !== $redirect) {
            self::assertSafeNavigationUrl($redirect, 'redirect');
            $response = $response->withHeader('X-Kongono-Redirect', $redirect);
        }

        return $response;
    }

    /** @param array<string, string|list<string>> $headers */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response = new self($status, $headers, Stream::fromString($encoded));

        return $response->hasHeader('Content-Type')
            ? $response
            : $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /** @param array<string, string|list<string>> $headers */
    public static function text(string $text, int $status = 200, array $headers = []): self
    {
        $response = new self($status, $headers, Stream::fromString($text));

        return $response->hasHeader('Content-Type')
            ? $response
            : $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    public static function redirect(string $location, int $status = 302): self
    {
        self::assertSafeNavigationUrl($location, 'redirect');

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
        self::assertValidProtocolVersion($version);
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
        $values = is_array($value) ? array_values($value) : [$value];
        self::assertValidHeader($name, $values);

        $clone = clone $this;
        $normalized = strtolower($name);
        if (isset($clone->headerNames[$normalized])) {
            unset($clone->headers[$clone->headerNames[$normalized]]);
        }
        $clone->headerNames[$normalized] = $name;
        $clone->headers[$name] = $values;

        return $clone;
    }

    public function withAddedHeader(string $name, $value): self
    {
        $values = is_array($value) ? array_values($value) : [$value];
        self::assertValidHeader($name, $values);

        $clone = clone $this;
        $normalized = strtolower($name);
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
        self::assertValidStatus($code, $reasonPhrase);
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = '' !== $reasonPhrase ? $reasonPhrase : (self::REASON_PHRASES[$code] ?? '');

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private static function assertValidStatus(int $code, string $reasonPhrase): void
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP status code %d (must be 100-599).', $code));
        }
        if ('' !== $reasonPhrase && 1 === preg_match('/[\x00-\x08\x0a-\x1f\x7f]/', $reasonPhrase)) {
            throw new InvalidArgumentException('Invalid HTTP reason phrase: contains control characters or CRLF.');
        }
    }

    private static function assertValidProtocolVersion(string $version): void
    {
        if (1 !== preg_match('/\A\d(?:\.\d)?\z/', $version)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP protocol version "%s".', $version));
        }
    }

    /**
     * Enforce RFC 9110 header name tokens and reject C0/DEL control characters (except HTAB).
     *
     * @param list<string> $values
     */
    private static function assertValidHeader(string $name, array $values): void
    {
        if (1 !== preg_match('/\A[a-zA-Z0-9!#$%&\'*+.^_`|~-]+\z/', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP header name "%s".', $name));
        }
        foreach ($values as $v) {
            if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $v)) {
                throw new InvalidArgumentException(sprintf('HTTP header "%s" value contains forbidden CRLF or control characters.', $name));
            }
        }
    }

    /**
     * Strict allowlist navigation URL validator: rejects ALL ASCII control chars (including tabs like `java\tscript:`
     * and C0 prefixes like `\x01javascript:` stripped by WHATWG browsers), permitting only safe relative paths or http(s) URLs.
     */
    private static function assertSafeNavigationUrl(string $url, string $context): void
    {
        if ('' === $url || 1 === preg_match('/[\x00-\x1F\x7F]/', $url)) {
            throw new InvalidArgumentException(sprintf('Unsafe %s URL: contains control characters or is empty.', $context));
        }

        $trimmed = trim($url);
        if (str_starts_with($trimmed, '/')) {
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/\\')) {
                throw new InvalidArgumentException(sprintf('Unsafe %s URL: protocol-relative redirect rejected.', $context));
            }

            return;
        }

        $parts = parse_url($trimmed);
        $scheme = is_array($parts) && is_string($parts['scheme'] ?? null) ? strtolower($parts['scheme']) : '';
        $host = is_array($parts) && is_string($parts['host'] ?? null) ? $parts['host'] : '';
        if (('http' === $scheme || 'https' === $scheme) && '' !== $host) {
            return;
        }

        throw new InvalidArgumentException(sprintf('Unsafe %s URL scheme rejected.', $context));
    }
}
