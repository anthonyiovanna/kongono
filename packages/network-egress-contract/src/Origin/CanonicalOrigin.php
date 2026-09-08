<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Origin;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Psr\Http\Message\UriInterface;

/** Stores canonical origin evidence without resolution, permission, or I/O. */
#[Api(ApiStatus::Experimental)]
final readonly class CanonicalOrigin
{
    private string $host;

    public function __construct(
        private OriginScheme $scheme,
        string $host,
        private int $port,
    ) {
        $this->host = self::canonicalHost($host);
        if ($port < 1 || $port > 65535) {
            throw InvalidNetworkEgressContractException::invalidOrigin('origin_port_range');
        }
    }

    public static function fromUri(UriInterface $uri): self
    {
        $scheme = match ($uri->getScheme()) {
            'http' => OriginScheme::Http,
            'https' => OriginScheme::Https,
            default => throw InvalidNetworkEgressContractException::invalidOrigin('uri_scheme_grammar'),
        };
        if ('' !== $uri->getUserInfo()) {
            throw InvalidNetworkEgressContractException::invalidOrigin('uri_user_info_present');
        }
        if ('' !== $uri->getFragment()) {
            throw InvalidNetworkEgressContractException::invalidOrigin('uri_fragment_present');
        }

        $host = $uri->getHost();
        if ('' === $host) {
            throw InvalidNetworkEgressContractException::invalidOrigin('uri_host_missing');
        }
        if (str_starts_with($host, '[') || str_ends_with($host, ']')) {
            if (!str_starts_with($host, '[') || !str_ends_with($host, ']')) {
                throw InvalidNetworkEgressContractException::invalidOrigin('uri_host_grammar');
            }
            $host = substr($host, 1, -1);
        }

        $port = $uri->getPort() ?? (OriginScheme::Http === $scheme ? 80 : 443);
        if ($port < 1 || $port > 65535) {
            throw InvalidNetworkEgressContractException::invalidOrigin('uri_port_range');
        }

        return new self($scheme, $host, $port);
    }

    public function scheme(): OriginScheme
    {
        return $this->scheme;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function equals(self $other): bool
    {
        return $this->scheme === $other->scheme
            && $this->host === $other->host
            && $this->port === $other->port;
    }

    private static function canonicalHost(string $host): string
    {
        $length = strlen($host);
        if ($length > 253) {
            throw InvalidNetworkEgressContractException::contractLimitExceeded('origin_host_bytes', 253);
        }
        if (
            $length < 1
            || 1 === preg_match('/[^\x00-\x7F]/', $host)
            || strtolower($host) !== $host
            || str_contains($host, '%')
            || str_contains($host, '[')
            || str_contains($host, ']')
        ) {
            throw InvalidNetworkEgressContractException::invalidOrigin('origin_host_grammar');
        }

        if (str_contains($host, ':')) {
            if (!self::isCanonicalIpv6($host)) {
                throw InvalidNetworkEgressContractException::invalidOrigin('origin_host_grammar');
            }

            return $host;
        }

        if (1 === preg_match('/\A[0-9.]+\z/D', $host)) {
            if (!self::isCanonicalIpv4($host)) {
                throw InvalidNetworkEgressContractException::invalidOrigin('origin_host_grammar');
            }

            return $host;
        }

        $labels = explode('.', $host);
        foreach ($labels as $label) {
            if (strlen($label) < 1 || strlen($label) > 63 || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/D', $label)) {
                throw InvalidNetworkEgressContractException::invalidOrigin('origin_host_grammar');
            }
        }

        return $host;
    }

    private static function isCanonicalIpv4(string $host): bool
    {
        $parts = explode('.', $host);
        if (4 !== count($parts)) {
            return false;
        }
        foreach ($parts as $part) {
            if ('' === $part || (strlen($part) > 1 && '0' === $part[0]) || 1 !== preg_match('/\A[0-9]{1,3}\z/D', $part) || (int) $part > 255) {
                return false;
            }
        }

        return true;
    }

    private static function isCanonicalIpv6(string $host): bool
    {
        if (1 !== preg_match('/\A[0-9a-f:]+\z/D', $host) || substr_count($host, '::') > 1) {
            return false;
        }

        $compressed = str_contains($host, '::');
        if ($compressed) {
            [$left, $right] = explode('::', $host, 2);
            $leftParts = '' === $left ? [] : explode(':', $left);
            $rightParts = '' === $right ? [] : explode(':', $right);
            if (count($leftParts) + count($rightParts) >= 8) {
                return false;
            }
            $parts = [...$leftParts, ...array_fill(0, 8 - count($leftParts) - count($rightParts), '0'), ...$rightParts];
        } else {
            $parts = explode(':', $host);
            if (8 !== count($parts)) {
                return false;
            }
        }

        foreach ($parts as $part) {
            if ('' === $part || strlen($part) > 4 || (strlen($part) > 1 && '0' === $part[0]) || 1 !== preg_match('/\A[0-9a-f]{1,4}\z/D', $part)) {
                return false;
            }
        }

        $bestStart = -1;
        $bestLength = 0;
        for ($start = 0; $start < 8; ++$start) {
            if ('0' !== $parts[$start]) {
                continue;
            }
            $length = 1;
            while ($start + $length < 8 && '0' === $parts[$start + $length]) {
                ++$length;
            }
            if ($length > $bestLength) {
                $bestStart = $start;
                $bestLength = $length;
            }
            $start += $length - 1;
        }

        if ($bestLength >= 2) {
            $left = implode(':', array_slice($parts, 0, $bestStart));
            $right = implode(':', array_slice($parts, $bestStart + $bestLength));
            $canonical = $left . '::' . $right;
        } else {
            $canonical = implode(':', $parts);
        }

        return $canonical === $host;
    }
}
