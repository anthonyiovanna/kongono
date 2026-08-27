<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\Exception\InvalidBrowserSessionValueException;

/** Carries one exact prevalidated HTTPS origin without normalization fallback. */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedOrigin
{
    public function __construct(private string $value)
    {
        if (strlen($value) > 2048 || 1 === preg_match('/[^\x21-\x7e]/D', $value)) {
            throw InvalidBrowserSessionValueException::trustedOrigin();
        }
        if (1 !== preg_match('/\Ahttps:\/\/(?<host>\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9.-]+)(?::(?<port>[0-9]{1,5}))?\z/D', $value, $matches)) {
            throw InvalidBrowserSessionValueException::trustedOrigin();
        }

        $host = $matches['host'];
        if (str_starts_with($host, '[')) {
            $address = substr($host, 1, -1);
            if (false === filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                throw InvalidBrowserSessionValueException::trustedOrigin();
            }
        } elseif (!$this->isValidHost($host)) {
            throw InvalidBrowserSessionValueException::trustedOrigin();
        }

        $port = $matches['port'] ?? '';
        if ('' !== $port && ((int) $port < 1 || (int) $port > 65_535)) {
            throw InvalidBrowserSessionValueException::trustedOrigin();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    private function isValidHost(string $host): bool
    {
        if (strlen($host) > 253 || str_starts_with($host, '.') || str_ends_with($host, '.')) {
            return false;
        }

        foreach (explode('.', $host) as $label) {
            if (strlen($label) > 63 || 1 !== preg_match('/\A[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\z/D', $label)) {
                return false;
            }
        }

        return true;
    }
}
