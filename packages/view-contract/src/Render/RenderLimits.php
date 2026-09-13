<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Render;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Exception\InvalidViewContractException;

/** Carries explicit finite render bounds without enforcing a runtime. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderLimits
{
    public function __construct(private int $maxOutputBytes, private int $maxRenderSteps)
    {
        if ($maxOutputBytes < 1 || $maxOutputBytes > 8388608) {
            throw InvalidViewContractException::invalidRenderLimits('max_output_bytes_range', 8388608);
        }
        if ($maxRenderSteps < 1 || $maxRenderSteps > 1000000) {
            throw InvalidViewContractException::invalidRenderLimits('max_render_steps_range', 1000000);
        }
    }

    public function maxOutputBytes(): int
    {
        return $this->maxOutputBytes;
    }

    public function maxRenderSteps(): int
    {
        return $this->maxRenderSteps;
    }

    public function equals(self $other): bool
    {
        return $this->maxOutputBytes === $other->maxOutputBytes
            && $this->maxRenderSteps === $other->maxRenderSteps;
    }
}
