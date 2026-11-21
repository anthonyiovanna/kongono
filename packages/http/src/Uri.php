<?php

declare(strict_types=1);

namespace Kongono\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/** Immutable PSR-7 URI implementation. */
final readonly class Uri implements UriInterface
{
    private const array STANDARD_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    public function __construct(
        private string $scheme = '',
        private string $userInfo = '',
        private string $host = '',
        private ?int $port = null,
        private string $path = '/',
        private string $query = '',
        private string $fragment = '',
    ) {}

    public static function parse(string $uri): self
    {
        if ('' === $uri) {
            return new self();
        }
        $parts = parse_url($uri);
        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('Unable to parse URI: "%s".', $uri));
        }

        $userInfo = $parts['user'] ?? '';
        if (isset($parts['pass'])) {
            $userInfo .= ':' . $parts['pass'];
        }

        return new self(
            scheme: strtolower($parts['scheme'] ?? ''),
            userInfo: $userInfo,
            host: strtolower($parts['host'] ?? ''),
            port: $parts['port'] ?? null,
            path: $parts['path'] ?? '/',
            query: $parts['query'] ?? '',
            fragment: $parts['fragment'] ?? '',
        );
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ('' === $this->host) {
            return '';
        }
        $authority = '' !== $this->userInfo ? $this->userInfo . '@' . $this->host : $this->host;
        $port = $this->getPort();
        if (null !== $port) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        if (null === $this->port) {
            return null;
        }
        if ((self::STANDARD_PORTS[$this->scheme] ?? null) === $this->port) {
            return null;
        }

        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        return new self(strtolower($scheme), $this->userInfo, $this->host, $this->port, $this->path, $this->query, $this->fragment);
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $info = null !== $password && '' !== $password ? $user . ':' . $password : $user;

        return new self($this->scheme, $info, $this->host, $this->port, $this->path, $this->query, $this->fragment);
    }

    public function withHost(string $host): UriInterface
    {
        return new self($this->scheme, $this->userInfo, strtolower($host), $this->port, $this->path, $this->query, $this->fragment);
    }

    public function withPort(?int $port): UriInterface
    {
        if (null !== $port && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException(sprintf('Invalid TCP port %d.', $port));
        }

        return new self($this->scheme, $this->userInfo, $this->host, $port, $this->path, $this->query, $this->fragment);
    }

    public function withPath(string $path): UriInterface
    {
        return new self($this->scheme, $this->userInfo, $this->host, $this->port, $path, $this->query, $this->fragment);
    }

    public function withQuery(string $query): UriInterface
    {
        return new self($this->scheme, $this->userInfo, $this->host, $this->port, $this->path, ltrim($query, '?'), $this->fragment);
    }

    public function withFragment(string $fragment): UriInterface
    {
        return new self($this->scheme, $this->userInfo, $this->host, $this->port, $this->path, $this->query, ltrim($fragment, '#'));
    }

    public function __toString(): string
    {
        $uri = '';
        if ('' !== $this->scheme) {
            $uri .= $this->scheme . ':';
        }
        $authority = $this->getAuthority();
        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }
        $uri .= $this->path;
        if ('' !== $this->query) {
            $uri .= '?' . $this->query;
        }
        if ('' !== $this->fragment) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}
