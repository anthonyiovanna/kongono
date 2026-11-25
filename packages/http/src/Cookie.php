<?php

declare(strict_types=1);

namespace Kongono\Http;

use InvalidArgumentException;

/** Immutable HTTP cookie value object (persistent-runtime safe — never calls PHP setcookie()). */
final readonly class Cookie
{
    public function __construct(
        public string $name,
        public string $value,
        public ?int $maxAge = null,
        public string $path = '/',
        public ?string $domain = null,
        public bool $secure = false,
        public bool $httpOnly = true,
        public string $sameSite = 'Lax',
    ) {
        if (1 !== preg_match('/\A[a-zA-Z0-9!#$%&\'*+.^_`|~-]+\z/', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid cookie name "%s".', $name));
        }
        if (1 === preg_match('/[\x00-\x1f\x7f;]/', $path)) {
            throw new InvalidArgumentException('Cookie path contains forbidden control or delimiter characters.');
        }
        if (null !== $domain && 1 === preg_match('/[\x00-\x20\x7f;]/', $domain)) {
            throw new InvalidArgumentException('Cookie domain contains forbidden characters.');
        }
        if ('' !== $sameSite && !in_array($sameSite, ['Strict', 'Lax', 'None'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid SameSite attribute "%s" (expected Strict, Lax, or None).', $sameSite));
        }
        if ('None' === $sameSite && !$secure) {
            throw new InvalidArgumentException('Cookies with SameSite=None must also set secure: true.');
        }
        if (str_starts_with($name, '__Secure-') && !$secure) {
            throw new InvalidArgumentException('Cookies prefixed with "__Secure-" must set secure: true.');
        }
        if (str_starts_with($name, '__Host-') && (!$secure || '/' !== $path || (null !== $domain && '' !== $domain))) {
            throw new InvalidArgumentException('Cookies prefixed with "__Host-" must set secure: true, path: "/", and omit domain.');
        }
    }

    public function toHeaderValue(): string
    {
        $parts = [sprintf('%s=%s', $this->name, rawurlencode($this->value))];
        if (null !== $this->maxAge) {
            $parts[] = 'Max-Age=' . $this->maxAge;
        }
        if ('' !== $this->path) {
            $parts[] = 'Path=' . $this->path;
        }
        if (null !== $this->domain && '' !== $this->domain) {
            $parts[] = 'Domain=' . $this->domain;
        }
        if ($this->secure) {
            $parts[] = 'Secure';
        }
        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }
        if ('' !== $this->sameSite) {
            $parts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $parts);
    }
}
