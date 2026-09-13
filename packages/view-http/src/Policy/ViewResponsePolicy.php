<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewHttp\Internal\ViewHttpGuard;

/** Carries explicit bounded caller-owned PSR-7 response policy. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewResponsePolicy
{
    /** @var list<ViewResponseHeader> */
    private array $headers;

    /** @param array<array-key, mixed> $headers */
    public function __construct(
        private int $statusCode,
        private string $reasonPhrase,
        private ViewContentType $contentType,
        array $headers = [],
    ) {
        $this->headers = ViewHttpGuard::policy($statusCode, $reasonPhrase, $headers);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function reasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function contentType(): ViewContentType
    {
        return $this->contentType;
    }

    /** @return list<ViewResponseHeader> */
    public function headers(): array
    {
        return $this->headers;
    }
}
