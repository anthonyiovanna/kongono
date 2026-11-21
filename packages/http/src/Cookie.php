<?php

declare(strict_types=1);

namespace Kongono\Http;

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
    ) {}

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
