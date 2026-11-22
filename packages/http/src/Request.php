<?php

declare(strict_types=1);

namespace Kongono\Http;

use JsonException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Immutable PSR-7 ServerRequest with modern ergonomic helpers
 * (JSON body, form input, route params, multipart uploads, content negotiation).
 */
final class Request implements ServerRequestInterface
{
    /** @var array<string, list<string>> */
    private array $headers = [];

    /** @var array<string, string> */
    private array $headerNames = [];

    private StreamInterface $body;

    private ?string $requestTarget = null;

    /**
     * @param array<string, string|list<string>>   $headers
     * @param array<string, mixed>                 $serverParams
     * @param array<string, string>                $cookieParams
     * @param array<string, mixed>                 $queryParams
     * @param array<string, UploadedFileInterface> $uploadedFiles
     * @param array<string, mixed>|object|null     $parsedBody
     * @param array<string, mixed>                 $attributes
     */
    public function __construct(
        private string $method,
        private UriInterface $uri,
        array $headers = [],
        ?StreamInterface $body = null,
        private string $protocolVersion = '1.1',
        private array $serverParams = [],
        private array $cookieParams = [],
        private array $queryParams = [],
        private array $uploadedFiles = [],
        private array|object|null $parsedBody = null,
        private array $attributes = [],
    ) {
        $this->method = strtoupper($method);
        $this->body = $body ?? Stream::fromString('');

        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            if (isset($this->headerNames[$normalized])) {
                unset($this->headers[$this->headerNames[$normalized]]);
            }
            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = is_array($value) ? $value : [$value];
        }
    }

    /**
     * Construct a Request cleanly for tests or programmatic invocations.
     *
     * @param array<string, string|list<string>>   $headers
     * @param array<string, mixed>                 $query
     * @param array<string, UploadedFileInterface> $files
     * @param array<string, mixed>|null            $parsedBody
     * @param array<string, mixed>                 $serverParams
     */
    public static function create(
        string $method,
        string $uri,
        array $headers = [],
        string $body = '',
        array $query = [],
        array $files = [],
        ?array $parsedBody = null,
        array $serverParams = [],
    ): self {
        // Prevent scheme-relative path smuggling (e.g. `//.env`) from being misparsed as a host
        if (str_starts_with($uri, '//')) {
            $tmpUri = Uri::parse('http://localhost' . $uri);
            $parsedUri = new Uri(path: $tmpUri->getPath(), query: $tmpUri->getQuery(), fragment: $tmpUri->getFragment());
        } else {
            $parsedUri = Uri::parse($uri);
        }
        if ([] === $query && '' !== $parsedUri->getQuery()) {
            parse_str($parsedUri->getQuery(), $parsedQuery);
            foreach ($parsedQuery as $k => $v) {
                $query[(string) $k] = $v;
            }
        }

        return new self(
            method: $method,
            uri: $parsedUri,
            headers: $headers,
            body: Stream::fromString($body),
            serverParams: $serverParams,
            queryParams: $query,
            uploadedFiles: $files,
            parsedBody: $parsedBody,
        );
    }

    /**
     * Construct a Request with a JSON-encoded payload.
     *
     * @param array<string, string|list<string>> $headers
     */
    public static function json(string $method, string $uri, mixed $payload, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] ??= 'application/json';
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return self::create($method, $uri, $headers, $encoded);
    }

    public const int DEFAULT_MAX_BODY_BYTES = 10_485_760;

    /** Capture a ServerRequest from standard PHP SAPI / CGI / FrankenPHP globals. */
    public static function fromGlobals(int $maxBodyBytes = self::DEFAULT_MAX_BODY_BYTES): self
    {
        /** @var array<string, mixed> $server */
        $server = $_SERVER;
        $method = is_string($server['REQUEST_METHOD'] ?? null) ? $server['REQUEST_METHOD'] : 'GET';
        $httpsValue = $server['HTTPS'] ?? null;
        $scheme = (is_string($httpsValue) && '' !== $httpsValue && 'off' !== strtolower($httpsValue)) ? 'https' : 'http';
        $rawHost = is_string($server['HTTP_HOST'] ?? null)
            ? trim($server['HTTP_HOST'])
            : (is_string($server['SERVER_NAME'] ?? null) ? trim($server['SERVER_NAME']) : 'localhost');
        // Defend against Host header injection (allow only RFC 3986 host[:port] with port 1..65535 or IPv6 literal)
        $host = 'localhost';
        if (1 === preg_match('/\A(\[[0-9a-fA-F:]+\]|[a-zA-Z0-9.-]+)(?::(\d{1,5}))?\z/', $rawHost, $hm)) {
            $portValid = !isset($hm[2]) || ((int) $hm[2] >= 1 && (int) $hm[2] <= 65535);
            if ($portValid) {
                $host = $rawHost;
            }
        }

        // Normalize REQUEST_URI strictly to origin-form so absolute-form or `@evil.com` can never hijack host
        $rawUri = is_string($server['REQUEST_URI'] ?? null) ? $server['REQUEST_URI'] : '/';
        if (1 === preg_match('/\Ahttps?:\/\//i', $rawUri)) {
            $absParts = parse_url($rawUri);
            $absPath = is_array($absParts) && is_string($absParts['path'] ?? null) ? $absParts['path'] : '/';
            $absQuery = is_array($absParts) && is_string($absParts['query'] ?? null) ? '?' . $absParts['query'] : '';
            $rawUri = (str_starts_with($absPath, '/') ? $absPath : '/' . $absPath) . $absQuery;
        } elseif (!str_starts_with($rawUri, '/')) {
            $rawUri = '/' . $rawUri;
        }
        $uri = Uri::parse(sprintf('%s://%s%s', $scheme, $host, $rawUri));

        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$name] = $value;
            }
        }

        $body = self::readBoundedPhpInput($maxBodyBytes);

        /** @var array<string, string> $cookies */
        $cookies = [];
        foreach ($_COOKIE as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $cookies[$k] = $v;
            }
        }

        /** @var array<string, mixed> $query */
        $query = [];
        foreach ($_GET as $k => $v) {
            if (is_string($k)) {
                $query[$k] = $v;
            }
        }

        $parsedBody = null;
        if ([] !== $_POST) {
            $parsedBody = [];
            foreach ($_POST as $k => $v) {
                $parsedBody[(string) $k] = $v;
            }
        }

        /** @var array<string, UploadedFileInterface> $files */
        $files = [];
        foreach ($_FILES as $field => $spec) {
            if (!is_string($field) || !is_array($spec) || !isset($spec['tmp_name']) || !is_string($spec['tmp_name'])) {
                continue;
            }
            $files[$field] = new UploadedFile(
                filePath: '' !== $spec['tmp_name'] ? $spec['tmp_name'] : null,
                size: isset($spec['size']) && is_int($spec['size']) ? $spec['size'] : null,
                error: isset($spec['error']) && is_int($spec['error']) ? $spec['error'] : UPLOAD_ERR_NO_FILE,
                clientFilename: isset($spec['name']) && is_string($spec['name']) ? $spec['name'] : null,
                clientMediaType: isset($spec['type']) && is_string($spec['type']) ? $spec['type'] : null,
            );
        }

        return new self(
            method: $method,
            uri: $uri,
            headers: $headers,
            body: $body,
            serverParams: $server,
            cookieParams: $cookies,
            queryParams: $query,
            uploadedFiles: $files,
            parsedBody: $parsedBody,
        );
    }

    // --- Ergonomic Kongono Accessors ---

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        $path = $this->uri->getPath();

        return '' === $path ? '/' : $path;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $line = $this->getHeaderLine($name);

        return '' === $line ? $default : $line;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return $this->queryParams;
        }

        return $this->queryParams[$key] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookieParams[$name] ?? $default;
    }

    /** Decode JSON request body into an associative array. */
    /** @return array<string, mixed> */
    public function jsonBody(): array
    {
        $raw = trim((string) $this->body);
        if ('' === $raw) {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new HttpException(400, 'Malformed JSON request body.', previous: $e);
        }

        if (!is_array($decoded)) {
            throw new HttpException(400, 'JSON request body must be an object.');
        }

        /** @var array<string, mixed> $result */
        $result = [];
        foreach ($decoded as $k => $v) {
            $result[(string) $k] = $v;
        }

        return $result;
    }

    /**
     * Return merged input from JSON body, POST form body, or query string.
     *
     * @return array<string, mixed>
     */
    public function allInput(): array
    {
        $contentType = strtolower($this->getHeaderLine('Content-Type'));
        if (str_contains($contentType, 'application/json')) {
            return [...$this->queryParams, ...$this->jsonBody()];
        }
        if (is_array($this->parsedBody)) {
            /** @var array<string, mixed> $body */
            $body = $this->parsedBody;

            return [...$this->queryParams, ...$body];
        }

        return $this->queryParams;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->allInput();

        return $all[$key] ?? $default;
    }

    public function param(string $name, ?string $default = null): ?string
    {
        $params = $this->attributes['_route_params'] ?? [];
        if (is_array($params) && isset($params[$name]) && is_string($params[$name])) {
            return $params[$name];
        }

        return $default;
    }

    public function file(string $name): ?UploadedFileInterface
    {
        return $this->uploadedFiles[$name] ?? null;
    }

    public function expectsJson(): bool
    {
        $accept = strtolower($this->getHeaderLine('Accept'));
        $contentType = strtolower($this->getHeaderLine('Content-Type'));
        $jsonPos = strpos($accept, 'application/json');
        $htmlPos = strpos($accept, 'text/html');

        // Kongono\Live fragment requests prefer HTML unless JSON is explicitly prioritized ahead of HTML
        if ($this->isLive() && (false === $jsonPos || (false !== $htmlPos && $htmlPos < $jsonPos))) {
            return false;
        }
        if (false !== $jsonPos && (false === $htmlPos || $jsonPos < $htmlPos)) {
            return true;
        }

        return str_contains($contentType, 'application/json')
            || str_starts_with($this->path(), '/api/');
    }

    // --- PSR-7 MessageInterface / RequestInterface / ServerRequestInterface ---

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

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $normalized = strtolower($name);
        $values = is_array($value) ? array_values($value) : [$value];
        if (isset($clone->headerNames[$normalized])) {
            $existingName = $clone->headerNames[$normalized];
            $clone->headers[$existingName] = [...$clone->headers[$existingName], ...$values];
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

        return $clone;
    }

    public function getRequestTarget(): string
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }
        $target = $this->uri->getPath();
        if ('' === $target) {
            $target = '/';
        }
        if ('' !== $this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $clone = clone $this;
        $clone->method = strtoupper($method);

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = $uri;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /** @return array<string, string> */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /** @param array<array-key, mixed> $cookies */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        $typed = [];
        foreach ($cookies as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $typed[$k] = $v;
            }
        }
        $clone->cookieParams = $typed;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /** @param array<array-key, mixed> $query */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        $typed = [];
        foreach ($query as $k => $v) {
            $typed[(string) $k] = $v;
        }
        $clone->queryParams = $typed;

        return $clone;
    }

    /** @return array<string, UploadedFileInterface> */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /** @param array<array-key, mixed> $uploadedFiles */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        $typed = [];
        foreach ($uploadedFiles as $k => $v) {
            if (is_string($k) && $v instanceof UploadedFileInterface) {
                $typed[$k] = $v;
            }
        }
        $clone->uploadedFiles = $typed;

        return $clone;
    }

    /** @return array<string, mixed>|object|null */
    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    /** @param array<array-key, mixed>|object|null $data */
    public function withParsedBody($data): ServerRequestInterface
    {
        $clone = clone $this;
        if (is_array($data)) {
            $typed = [];
            foreach ($data as $k => $v) {
                $typed[(string) $k] = $v;
            }
            $clone->parsedBody = $typed;
        } else {
            $clone->parsedBody = $data;
        }

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    /** Returns true when invoked by the Kongono\Live browser runtime (`X-Kongono-Live: true`). */
    public function isLive(): bool
    {
        return 'true' === strtolower(trim($this->getHeaderLine('X-Kongono-Live')));
    }

    /** Returns true when invoked by the Kongono Bridge SPA runtime (`X-Kongono-Bridge: true`). */
    public function isBridge(): bool
    {
        return 'true' === strtolower(trim($this->getHeaderLine('X-Kongono-Bridge')));
    }

    /** Return requested partial-reload prop keys (`X-Kongono-Bridge-Partial-Data: tasks,metrics`). */
    /** @return list<string> */
    public function bridgePartialData(): array
    {
        $raw = trim($this->getHeaderLine('X-Kongono-Bridge-Partial-Data'));
        if ('' === $raw) {
            return [];
        }

        $keys = [];
        foreach (explode(',', $raw) as $part) {
            $clean = trim($part);
            if ('' !== $clean) {
                $keys[] = $clean;
            }
        }

        return array_values(array_unique($keys));
    }

    /** Return the component name requested for a Bridge partial reload (`X-Kongono-Bridge-Partial-Component`). */
    public function bridgePartialComponent(): ?string
    {
        $raw = trim($this->getHeaderLine('X-Kongono-Bridge-Partial-Component'));

        return '' !== $raw ? $raw : null;
    }

    private static function readBoundedPhpInput(int $maxBodyBytes): Stream
    {
        $input = @fopen('php://input', 'rb');
        if (false === $input) {
            return Stream::fromString('');
        }

        $cap = max(0, $maxBodyBytes);
        $buf = '';
        $exceeded = false;
        try {
            while (!feof($input)) {
                $chunk = fread($input, 65536);
                if (false === $chunk || '' === $chunk) {
                    break;
                }
                if (strlen($buf) + strlen($chunk) > $cap) {
                    $exceeded = true;
                    // On PHP-FPM, drain a bounded tail before closing to mitigate CVE-2026-129289 unread-body smuggling
                    if ('fpm-fcgi' === PHP_SAPI) {
                        $drained = strlen($chunk);
                        $drainCap = 2_097_152;
                        while (!feof($input) && $drained < $drainCap) {
                            $extra = fread($input, 65536);
                            if (false === $extra || '' === $extra) {
                                break;
                            }
                            $drained += strlen($extra);
                        }
                    }
                    break;
                }
                $buf .= $chunk;
            }
        } finally {
            fclose($input);
        }

        if ($exceeded) {
            throw new HttpException(
                413,
                sprintf('Request body exceeds maximum allowed size of %d bytes.', $cap),
                headers: ['Connection' => 'close'],
            );
        }

        return Stream::fromString($buf);
    }
}
