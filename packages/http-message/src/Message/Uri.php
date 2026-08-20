<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Message;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Psr\Http\Message\UriInterface;

/** Implements deterministic RFC 3986 component storage without network lookup. */
#[Api(ApiStatus::Experimental)]
final class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if (1 !== preg_match('~^(?:([A-Za-z][A-Za-z0-9+.-]*):)?(?://([^/?#]*))?([^?#]*)(?:\?([^#]*))?(?:#(.*))?$~sD', $uri, $matches)) {
            throw self::invalidSyntax($uri, 'structure');
        }
        $this->scheme = strtolower($matches[1]);
        $authority = $matches[2];
        if ('' !== $authority) {
            $this->parseAuthority($authority);
        }
        $this->path = self::encodeComponent($matches[3], 'path');
        $this->query = self::encodeComponent($matches[4] ?? '', 'query');
        $this->fragment = self::encodeComponent($matches[5] ?? '', 'fragment');
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
        $port = $this->getPort();
        return ('' === $this->userInfo ? '' : $this->userInfo . '@') . $this->host . (null === $port ? '' : ':' . $port);
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
        return self::normalizePort($this->port, $this->scheme);
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
        if ('' !== $scheme && 1 !== preg_match('/^[A-Za-z][A-Za-z0-9+.-]*$/D', $scheme)) {
            throw self::invalidComponent('scheme', $scheme, 'syntax');
        }
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $clone = clone $this;
        $encoded = self::encodeComponent($user, 'userinfo');
        $clone->userInfo = null === $password ? $encoded : $encoded . ':' . self::encodeComponent($password, 'userinfo');
        return $clone;
    }

    public function withHost(string $host): UriInterface
    {
        $clone = clone $this;
        $clone->host = self::normalizeHost($host);
        return $clone;
    }

    public function withPort(?int $port): UriInterface
    {
        if (null !== $port && ($port < 1 || $port > 65535)) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUriPort, ['port' => $port]);
        }
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    public function withPath(string $path): UriInterface
    {
        $clone = clone $this;
        $clone->path = self::encodeComponent($path, 'path');
        return $clone;
    }

    public function withQuery(string $query): UriInterface
    {
        $clone = clone $this;
        $clone->query = self::encodeComponent(str_starts_with($query, '?') ? substr($query, 1) : $query, 'query');
        return $clone;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $clone = clone $this;
        $clone->fragment = self::encodeComponent(str_starts_with($fragment, '#') ? substr($fragment, 1) : $fragment, 'fragment');
        return $clone;
    }

    public function __toString(): string
    {
        $uri = '' === $this->scheme ? '' : $this->scheme . ':';
        $authority = $this->getAuthority();
        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }
        $path = $this->path;
        if ('' !== $authority && '' !== $path && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        if ('' === $authority && str_starts_with($path, '//')) {
            $path = '/' . ltrim($path, '/');
        }
        $uri .= $path;
        if ('' !== $this->query) {
            $uri .= '?' . $this->query;
        }
        if ('' !== $this->fragment) {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }

    private function parseAuthority(string $authority): void
    {
        $hostPort = $authority;
        $at = strrpos($authority, '@');
        if (false !== $at) {
            $this->userInfo = self::encodeComponent(substr($authority, 0, $at), 'userinfo');
            $hostPort = substr($authority, $at + 1);
        }
        if (str_starts_with($hostPort, '[')) {
            $end = strpos($hostPort, ']');
            if (false === $end) {
                throw self::invalidSyntax($authority, 'host');
            }
            $host = substr($hostPort, 0, $end + 1);
            $remainder = substr($hostPort, $end + 1);
            if ('' !== $remainder) {
                if (!str_starts_with($remainder, ':') || !ctype_digit(substr($remainder, 1))) {
                    throw self::invalidSyntax($authority, 'port');
                }
                $this->port = self::validatedPort(substr($remainder, 1));
            }
        } else {
            $colon = strrpos($hostPort, ':');
            if (false !== $colon) {
                $port = substr($hostPort, $colon + 1);
                if ('' === $port || !ctype_digit($port) || str_contains(substr($hostPort, 0, $colon), ':')) {
                    throw self::invalidSyntax($authority, 'port');
                }
                $this->port = self::validatedPort($port);
                $host = substr($hostPort, 0, $colon);
            } else {
                $host = $hostPort;
            }
        }
        $this->host = self::normalizeHost($host);
        if ('' === $this->host && ('' !== $this->userInfo || null !== $this->port)) {
            throw self::invalidSyntax($authority, 'authority');
        }
    }

    private static function normalizeHost(string $host): string
    {
        if ('' === $host) {
            return '';
        }
        if (1 === preg_match('/[^\x00-\x7F]/', $host)) {
            throw self::invalidComponent('host', $host, 'non_ascii');
        }
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $literal = substr($host, 1, -1);
            if (false === @inet_pton($literal)) {
                throw self::invalidComponent('host', $host, 'ip_literal');
            }
            return '[' . strtolower($literal) . ']';
        }
        if (false !== filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return strtolower($host);
        }
        self::assertPercentTriplets($host, 'host');
        if (1 !== preg_match("/^(?:[A-Za-z0-9._~-]|[!$&'()*+,;=]|%[0-9A-Fa-f]{2})+$/D", $host)) {
            throw self::invalidComponent('host', $host, 'reg_name');
        }
        return strtolower($host);
    }

    private static function encodeComponent(string $value, string $component): string
    {
        self::assertPercentTriplets($value, $component);
        $allowed = match ($component) {
            'userinfo' => "A-Za-z0-9._~!$&'()*+,;=:",
            'path' => "A-Za-z0-9._~!$&'()*+,;=:@/",
            'query', 'fragment' => "A-Za-z0-9._~!$&'()*+,;=:@/?",
            default => throw new \LogicException('Unknown URI component.'),
        };
        $output = '';
        for ($i = 0, $length = strlen($value); $i < $length; ++$i) {
            if ('%' === $value[$i]) {
                $output .= substr($value, $i, 3);
                $i += 2;
                continue;
            }
            $byte = ord($value[$i]);
            if ($byte < 128 && 1 === preg_match('#^[' . $allowed . ']$#D', $value[$i])) {
                $output .= $value[$i];
            } else {
                $output .= sprintf('%%%02X', $byte);
            }
        }
        return $output;
    }

    private static function assertPercentTriplets(string $value, string $component): void
    {
        for ($i = 0, $length = strlen($value); $i < $length; ++$i) {
            if ('%' === $value[$i] && ($i + 2 >= $length || !ctype_xdigit($value[$i + 1] . $value[$i + 2]))) {
                throw self::invalidComponent($component, $value, 'percent_triplet');
            }
        }
    }

    private static function validatedPort(string $port): int
    {
        $integer = (int) $port;
        if ($integer < 1 || $integer > 65535) {
            throw new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUriPort, ['port' => $integer]);
        }
        return $integer;
    }

    private static function normalizePort(?int $port, string $scheme): ?int
    {
        return ('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port) ? null : $port;
    }

    private static function invalidSyntax(string $value, string $violation): InvalidMessageArgumentException
    {
        return new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUriSyntax, ['actual_length' => strlen($value), 'violation' => $violation]);
    }

    private static function invalidComponent(string $component, string $value, string $violation): InvalidMessageArgumentException
    {
        return new InvalidMessageArgumentException(HttpMessageDiagnostic::InvalidUriComponent, ['component' => $component, 'component_length' => strlen($value), 'violation' => $violation]);
    }
}
