<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Render;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;

/** Carries exact caller-supplied completed presentation bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderedOutput
{
    private string $bytes;

    public function __construct(string $bytes, RenderLimits $limits)
    {
        $this->bytes = ContractGuard::renderedOutput($bytes, $limits->maxOutputBytes());
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function byteCount(): int
    {
        return strlen($this->bytes);
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }
}
